<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Notification List - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Notification List</h1>
        <div class="mb-4 flex justify-between items-center">
            <input v-model="search" type="text" placeholder="Search notifications..." class="border rounded px-3 py-2 w-1/3" />
            <button @click="goToAdd" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Notification</button>
        </div>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">Order ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Type</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Message</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Created At</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="notification in filteredNotifications" :key="notification.id" class="hover:bg-gray-100">
                    <td class="border border-gray-300 px-4 py-2">{{ notification.order_id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ notification.type }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ notification.status }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ notification.message }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ notification.created_at }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button @click="editNotification(notification.id)" class="text-blue-600 hover:underline mr-2">Edit</button>
                        <button @click="deleteNotification(notification.id)" class="text-red-600 hover:underline">Delete</button>
                    </td>
                </tr>
                <tr v-if="filteredNotifications.length === 0">
                    <td colspan="6" class="text-center py-4">No notifications found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    search: '',
                    notifications: <?php
                        $notificationsArray = [];
                        while ($row = $notifications->fetch(PDO::FETCH_ASSOC)) {
                            $notificationsArray[] = $row;
                        }
                        echo json_encode($notificationsArray);
                    ?>
                };
            },
            computed: {
                filteredNotifications() {
                    if (!this.search) return this.notifications;
                    return this.notifications.filter(n =>
                        n.order_id.toString().includes(this.search) ||
                        n.type.toLowerCase().includes(this.search.toLowerCase()) ||
                        n.status.toLowerCase().includes(this.search.toLowerCase()) ||
                        n.message.toLowerCase().includes(this.search.toLowerCase())
                    );
                }
            },
            methods: {
                goToAdd() {
                    window.location.href = '/notifications/add';
                },
                editNotification(id) {
                    window.location.href = `/notifications/edit/${id}`;
                },
                deleteNotification(id) {
                    if (confirm('Are you sure you want to delete this notification?')) {
                        fetch(`/notifications/delete/${id}`, { method: 'POST' })
                            .then(() => window.location.reload())
                            .catch(err => alert('Failed to delete notification.'));
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
