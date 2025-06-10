import React, { useState, useEffect } from 'react';
import { Col, Container, Row } from 'react-bootstrap';
import { motion, useAnimation } from 'framer-motion';
import { useInView } from 'react-intersection-observer';

export default function MemberCounter() {
    const [counters, setCounters] = useState({
        members: 0,
        online: 0,
        premium: 0,
        newToday: 0
    });

    const targetCounters = {
        members: 12543,
        online: 842,
        premium: 3165,
        newToday: 127
    };

    const controls = useAnimation();
    const [ref, inView] = useInView({
        threshold: 0.3,
        triggerOnce: true
    });

    useEffect(() => {
        if (inView) {
            controls.start("visible");
            startCounterAnimation();
        }
    }, [inView, controls]);

    const startCounterAnimation = () => {
        const duration = 2; // seconds
        const increment = (target, current, duration) => {
            const step = target / (duration * 60); // 60fps

            const updateCounter = () => {
                setCounters(prev => {
                    const newCounters = { ...prev };
                    let allComplete = true;

                    for (const key in targetCounters) {
                        if (prev[key] < targetCounters[key]) {
                            newCounters[key] = Math.min(
                                prev[key] + Math.ceil(step),
                                targetCounters[key]
                            );
                            allComplete = false;
                        }
                    }

                    return newCounters;
                });

                if (Object.values(counters).some(v => v < Object.values(targetCounters)[0])) {
                    requestAnimationFrame(updateCounter);
                }
            };

            updateCounter();
        };

        increment(targetCounters.members, counters.members, duration);
    };

    const counterItems = [
        {
            title: "Total Members",
            value: counters.members,
            icon: "👥",
            color: "purple-pink",
            gradient: "linear-gradient(135deg, #9d4edd 0%, #ff3e9d 100%)"
        },
        {
            title: "Online Now",
            value: counters.online,
            icon: "🟢",
            color: "green-teal",
            gradient: "linear-gradient(135deg, #4ade80 0%, #14b8a6 100%)"
        },
        {
            title: "Premium Members",
            value: counters.premium,
            icon: "⭐",
            color: "yellow-orange",
            gradient: "linear-gradient(135deg, #facc15 0%, #f97316 100%)"
        },
        {
            title: "New Today",
            value: counters.newToday,
            icon: "🆕",
            color: "blue-cyan",
            gradient: "linear-gradient(135deg, #60a5fa 0%, #06b6d4 100%)"
        }
    ];

    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.2,
                delayChildren: 0.3
            }
        }
    };

    const itemVariants = {
        hidden: { y: 50, opacity: 0 },
        visible: {
            y: 0,
            opacity: 1,
            transition: {
                type: "spring",
                stiffness: 100,
                damping: 10
            }
        }
    };

    const counterVariants = {
        hidden: { scale: 0.8 },
        visible: {
            scale: 1,
            transition: {
                type: "spring",
                stiffness: 200,
                damping: 15
            }
        }
    };

    return (
        <div className="love-icon-bg py-5" ref={ref}>
            <Container>
                {/* Header Section */}
                <motion.div
                    initial="hidden"
                    animate={controls}
                    variants={containerVariants}
                >
                    <Row className="justify-content-center mb-5">
                        <Col lg={8} className="text-center">
                            <motion.h1
                                className="section-title"
                                variants={itemVariants}
                            >
                                It All Starts With A Date
                            </motion.h1>
                            <motion.div
                                className="divider"
                                variants={itemVariants}
                            ></motion.div>
                            <motion.p
                                className="section-subtitle"
                                variants={itemVariants}
                            >
                                Become part of a growing community and have access to thousands of people.
                            </motion.p>
                        </Col>
                    </Row>

                    <Row className="justify-content-center">
                        {counterItems.map((item, index) => (
                            <Col
                                key={index}
                                md={3}
                                className="mb-4 text-center p-3"
                            >
                                <motion.div
                                    className={`rounded shadow p-4 h-100`}
                                    style={{
                                        background: item.gradient,
                                        color: 'white'
                                    }}
                                    variants={itemVariants}
                                    whileHover={{
                                        scale: 1.05,
                                        boxShadow: "0 15px 30px rgba(0, 0, 0, 0.3)"
                                    }}
                                >
                                    <motion.div
                                        className="counter-icon mb-3"
                                        variants={counterVariants}
                                        style={{
                                            fontSize: '2rem',
                                            display: 'inline-block',
                                            background: 'rgba(255, 255, 255, 0.2)',
                                            borderRadius: '50%',
                                            width: '60px',
                                            height: '60px',
                                            lineHeight: '60px'
                                        }}
                                    >
                                        {item.icon}
                                    </motion.div>
                                    <motion.h2
                                        className="mb-3"
                                        variants={counterVariants}
                                        style={{
                                            fontSize: '2.5rem',
                                            fontWeight: '700'
                                        }}
                                    >
                                        {item.value.toLocaleString()}
                                    </motion.h2>
                                    <motion.p
                                        variants={itemVariants}
                                        style={{
                                            fontSize: '1.1rem',
                                            marginBottom: '0'
                                        }}
                                    >
                                        {item.title}
                                    </motion.p>
                                </motion.div>
                            </Col>
                        ))}
                    </Row>
                </motion.div>
            </Container>
        </div>
    );
}