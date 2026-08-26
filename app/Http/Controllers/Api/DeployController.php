<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    protected string $deployToken;

    protected string $deployScript;

    /**
     * Branch-to-app mapping — built from .env config so new apps
     * can be added without code changes.
     */
    protected array $branchMap;

    public function __construct()
    {
        $this->deployToken = config('services.deploy.token', '');
        $this->deployScript = config('services.deploy.script', '');

        $this->branchMap = [
            config('services.deploy.staging_branch', 'develop') => [
                'app_dir' => config('services.deploy.staging_dir'),
                'branch' => config('services.deploy.staging_branch', 'develop'),
            ],
            config('services.deploy.prod_branch', 'main') => [
                'app_dir' => config('services.deploy.prod_dir'),
                'branch' => config('services.deploy.prod_branch', 'main'),
            ],
        ];
    }

    /**
     * GitHub webhook endpoint — triggered on push events.
     *
     * Verifies the deploy token, resolves the branch to an app directory,
     * and runs deploy.sh in the background. Returns immediately so
     * GitHub doesn't timeout.
     */
    public function webhook(Request $request): JsonResponse
    {
        // 1. Verify the deploy token — accept header (curl testing) or
        //    query parameter (GitHub webhooks don't support custom headers).
        $token = $request->header('X-Deploy-Token', '') ?: $request->query('token', '');

        if (! hash_equals($this->deployToken, $token)) {
            Log::warning('Deploy webhook: invalid token', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
            ], 403);
        }

        // 2. Ignore non-push events (ping, etc.) — return 200 so GitHub
        //    marks the delivery as successful.
        $event = $request->header('X-GitHub-Event', '');

        if ($event !== 'push') {
            Log::info('Deploy webhook: ignoring event', ['event' => $event]);

            return response()->json([
                'status' => 'ignored',
                'message' => "Event '{$event}' ignored — only push events trigger deployment",
            ]);
        }

        // 3. Parse the branch from the GitHub push payload.
        $ref = $request->input('ref', '');

        if ($ref === '') {
            Log::warning('Deploy webhook: missing ref in push payload');

            return response()->json([
                'status' => 'error',
                'message' => 'Missing ref',
            ], 400);
        }

        $branch = str_replace('refs/heads/', '', $ref);

        // 4. Resolve branch to app config.
        $config = $this->branchMap[$branch] ?? null;

        if ($config === null) {
            Log::info('Deploy webhook: ignoring push to non-deployed branch', [
                'branch' => $branch,
            ]);

            return response()->json([
                'status' => 'ignored',
                'message' => "Branch '{$branch}' is not configured for deployment",
            ]);
        }

        // 5. Run deploy.sh in the background.
        $repoUrl = config('services.deploy.repo_url', '');
        $cmd = sprintf(
            'nohup bash %s %s %s %s > /dev/null 2>&1 &',
            escapeshellarg($this->deployScript),
            escapeshellarg($config['app_dir']),
            escapeshellarg($config['branch']),
            escapeshellarg($repoUrl),
        );

        exec($cmd, $output, $returnCode);

        Log::info('Deploy webhook: triggered', [
            'branch' => $branch,
            'app_dir' => $config['app_dir'],
            'exit' => $returnCode,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Deployment triggered for {$branch}",
            'app_dir' => $config['app_dir'],
            'branch' => $branch,
        ]);
    }
}
