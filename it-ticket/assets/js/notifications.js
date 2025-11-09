// Initialize notification state in localStorage if not exists
if (!localStorage.getItem('notificationState')) {
    localStorage.setItem('notificationState', JSON.stringify({
        lastNotificationCount: 0,
        lastMessageCount: 0,
        lastCheckTime: Date.now()
    }));
}

// Audio elements for notifications
const notificationSound = new Audio('assets/sounds/notification.mp3');
const messageSound = new Audio('assets/sounds/notification.mp3');

// Function to update notification and message counts
function updateCounts() {
    fetch('get_notification_counts.php')
        .then(response => response.json())
        .then(data => {
            const currentTime = Date.now();
            const notificationState = JSON.parse(localStorage.getItem('notificationState'));
            const timeSinceLastCheck = currentTime - notificationState.lastCheckTime;

            // Only play sound if it's been more than 2 seconds since last check
            if (timeSinceLastCheck > 2000) {
                // Update notification count and play sound if new notification
                const notificationBadge = document.querySelector('.fa-bell').nextElementSibling;
                if (data.notifications > 0) {
                    if (!notificationBadge) {
                        const badge = document.createElement('span');
                        badge.className = 'badge badge-important';
                        document.querySelector('.fa-bell').parentNode.appendChild(badge);
                    }
                    document.querySelector('.fa-bell').nextElementSibling.textContent = data.notifications;
                    
                    // Play sound if new notification
                    if (data.notifications > notificationState.lastNotificationCount) {
                        notificationSound.play().catch(error => console.log('Error playing notification sound:', error));
                    }
                } else if (notificationBadge) {
                    notificationBadge.remove();
                }
                notificationState.lastNotificationCount = data.notifications;

                // Update message count and play sound if new message
                const messageBadge = document.querySelector('.fa-envelope').nextElementSibling;
                if (data.messages > 0) {
                    if (!messageBadge) {
                        const badge = document.createElement('span');
                        badge.className = 'badge badge-success';
                        document.querySelector('.fa-envelope').parentNode.appendChild(badge);
                    }
                    document.querySelector('.fa-envelope').nextElementSibling.textContent = data.messages;
                    
                    // Play sound if new message
                    if (data.messages > notificationState.lastMessageCount) {
                        messageSound.play().catch(error => console.log('Error playing message sound:', error));
                    }
                } else if (messageBadge) {
                    messageBadge.remove();
                }
                notificationState.lastMessageCount = data.messages;
            }

            // Update the last check time
            notificationState.lastCheckTime = currentTime;
            localStorage.setItem('notificationState', JSON.stringify(notificationState));
        })
        .catch(error => console.error('Error fetching counts:', error));
}

// Function to mark notification as read
function markNotificationRead(event, element) {
    event.preventDefault();
    const notificationId = element.getAttribute('data-notification-id');
    const href = element.getAttribute('href');
    const notificationItem = element.closest('li');
    const notificationsList = document.querySelector('.dropdown-navbar.navbar-pink');

    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update notification count in badge
            const badge = document.querySelector('.fa-bell').nextElementSibling;
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }

            // Update count in header
            const headerCount = document.querySelector('.dropdown-header .ace-icon + span');
            if (headerCount) {
                const count = parseInt(headerCount.textContent) - 1;
                headerCount.textContent = count + ' New Notifications';
            }

            // Remove the notification item
            if (notificationItem) {
                notificationItem.remove();
            }

            // If no more notifications, show "No new notifications" message
            if (notificationsList && notificationsList.querySelectorAll('li').length <= 1) {
                notificationsList.innerHTML = `
                    <li>
                        <a href="#">
                            <div class="clearfix">
                                <span class="pull-left">No new notifications</span>
                            </div>
                        </a>
                    </li>`;
            }

            // Redirect to the thread
            window.location.href = href;
        } else {
            console.error('Failed to mark notification as read:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Function to mark message as read
function markMessageRead(event, element) {
    event.preventDefault();
    const messageId = element.getAttribute('data-message-id');
    const href = element.getAttribute('href');
    const messageItem = element.closest('li');
    const messagesList = document.querySelector('.dropdown-navbar:not(.navbar-pink)');
    const readMessagesSection = messagesList.querySelector('.dropdown-header .ace-icon.fa-history')?.closest('li')?.nextElementSibling;

    fetch('mark_message_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'message_id=' + messageId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update message count in badge
            const badge = document.querySelector('.fa-envelope').nextElementSibling;
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }

            // Update count in header
            const headerCount = document.querySelector('.dropdown-header .ace-icon.fa-envelope-o + span');
            if (headerCount) {
                const count = parseInt(headerCount.textContent) - 1;
                headerCount.textContent = count + ' Messages';
            }

            // Instead of removing the message, move it to the read messages section
            if (messageItem) {
                // Remove the onclick attribute and message-link class
                const messageLink = messageItem.querySelector('a');
                if (messageLink) {
                    messageLink.removeAttribute('onclick');
                    messageLink.classList.remove('message-link');
                }

                // Check if read messages section exists
                if (readMessagesSection) {
                    // Move the message to the read messages section
                    readMessagesSection.appendChild(messageItem);
                } else {
                    // Create read messages section if it doesn't exist
                    const divider = document.createElement('li');
                    divider.className = 'divider';
                    
                    const readHeader = document.createElement('li');
                    readHeader.className = 'dropdown-header';
                    readHeader.innerHTML = '<i class="ace-icon fa fa-history"></i> Read Messages';
                    
                    const readMessagesList = document.createElement('li');
                    readMessagesList.appendChild(messageItem);
                    
                    // Add the new elements to the messages list
                    messagesList.appendChild(divider);
                    messagesList.appendChild(readHeader);
                    messagesList.appendChild(readMessagesList);
                }
            }

            // If no more unread messages, show "No new messages" message
            const unreadMessages = messagesList.querySelectorAll('li a.message-link');
            if (unreadMessages.length === 0) {
                const noMessagesItem = document.createElement('li');
                noMessagesItem.innerHTML = `
                    <a href="#" class="clearfix">
                        <span class="msg-body">
                            <span class="msg-title">No new messages</span>
                        </span>
                    </a>
                `;
                messagesList.insertBefore(noMessagesItem, messagesList.firstChild);
            }

            // Redirect to the thread
            window.location.href = href;
        } else {
            console.error('Failed to mark message as read:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking message as read:', error);
    });
}

// Start checking for updates
setInterval(updateCounts, 3000);
updateCounts(); 