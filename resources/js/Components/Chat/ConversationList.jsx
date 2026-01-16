import React from "react";
import {
    Form,
    InputGroup,
    Button,
    Nav,
    NavItem,
    NavLink as BootstrapNavLink,
} from "react-bootstrap";
import { MoreVertical, Search } from "lucide-react";
import ConversationItem from "./ConversationItem";
import { EmptyConversationList } from "./EmptyStates";
import { usePage } from "@inertiajs/react";

const ConversationList = ({
    searchTerm,
    setSearchTerm,
    activeFilter,
    setActiveFilter,
    filteredConversations,
    chatConversation,
    getOtherUser,
    formatTimeMemo,
    getMessageStatus,
    getMessageStatusIcon,
    handleChatSelect,
}) => {
    const { auth } = usePage().props;

    return (
        <div
            className="h-100 d-flex flex-column p-2 border-end border-dark"
            style={{ background: "#1a1a1a" }}
        >
            {/* Header */}
            <div className="p-2">
                <div className="d-flex align-items-center justify-content-between mb-3">
                    <h4 className="text-white mb-0">Chats</h4>
                    <Button
                        variant="link"
                        size="sm"
                        className="p-0 text-white-50"
                    >
                        <MoreVertical size={20} />
                    </Button>
                </div>

                {/* Search */}
                <InputGroup className="search-group rounded-pill overflow-hidden bg-dark">
                    <InputGroup.Text className="bg-dark border-0">
                        <Search size={18} className="text-white-50" />
                    </InputGroup.Text>
                    <Form.Control
                        type="search"
                        placeholder="Search or start a new chat"
                        className="bg-dark border-0 text-white"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </InputGroup>
            </div>

            {/* Filter Tabs */}
            <Nav variant="pills" className="p-2">
                {["All", "Unread", "Favourites"].map((filter) => (
                    <NavItem key={filter}>
                        <BootstrapNavLink
                            active={activeFilter === filter}
                            onClick={() => setActiveFilter(filter)}
                            className={`px-3 py-1 rounded-pill border-0 ${
                                activeFilter === filter
                                    ? "bg-warning text-dark"
                                    : "text-white-50"
                            }`}
                            style={{ fontSize: "0.875rem" }}
                        >
                            {filter}
                        </BootstrapNavLink>
                    </NavItem>
                ))}
            </Nav>

            {/* Conversation List */}
            <div className="flex-grow-1 overflow-auto">
                {filteredConversations.length > 0 ? (
                    filteredConversations.map((conversation) => (
                        <ConversationItem
                            key={conversation.id}
                            conversation={conversation}
                            isActive={chatConversation?.id === conversation.id}
                            auth={auth}
                            getOtherUser={getOtherUser}
                            formatTime={formatTimeMemo}
                            getMessageStatus={getMessageStatus}
                            getMessageStatusIcon={getMessageStatusIcon}
                            onSelect={handleChatSelect}
                        />
                    ))
                ) : (
                    <EmptyConversationList />
                )}
            </div>
        </div>
    );
};

export default ConversationList;
