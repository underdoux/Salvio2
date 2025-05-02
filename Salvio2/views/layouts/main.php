<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Pharma - <?= $pageTitle ?? 'Dashboard' ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Page-specific CSS -->
    <?php if (strpos($_SERVER['REQUEST_URI'], '/products') === 0): ?>
    <link rel="stylesheet" href="/assets/css/pages/products.css">
    <?php endif; ?>
    
    <?php if (strpos($_SERVER['REQUEST_URI'], '/orders') === 0): ?>
    <link rel="stylesheet" href="/assets/css/pages/orders.css">
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="/assets/js/app.js" defer></script>

    <!-- Page-specific Components -->
    <?php if (strpos($_SERVER['REQUEST_URI'], '/products') === 0): ?>
    <script src="/assets/js/components/products/ProductList.js"></script>
    <script src="/assets/js/components/products/ProductFormModal.js"></script>
    <script src="/assets/js/components/products/StockAdjustmentModal.js"></script>
    <script src="/assets/js/components/products/ImportModal.js"></script>
    <?php endif; ?>

    <?php if (strpos($_SERVER['REQUEST_URI'], '/orders') === 0): ?>
    <script src="/assets/js/components/orders/OrderList.js"></script>
    <script src="/assets/js/components/orders/OrderFormModal.js"></script>
    <script src="/assets/js/components/orders/PaymentModal.js"></script>
    <?php endif; ?>
</head>
<body>
    <div id="app" class="layout-wrapper" :class="{'menu-expanded': isMenuExpanded}">
        <!-- Sidebar -->
        <aside class="sidebar" v-if="isAuthenticated">
            <div class="sidebar-header">
                <img src="/assets/images/logo.png" alt="POS Pharma" class="logo">
                <button @click="toggleMenu" class="menu-toggle">
                    <i class="fas" :class="isMenuExpanded ? 'fa-times' : 'fa-bars'"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li v-if="hasPermission('view_dashboard')">
                        <a href="/dashboard" :class="{'active': currentPage === 'dashboard'}">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li v-if="hasPermission('view_products')">
                        <a href="/products" :class="{'active': currentPage === 'products'}">
                            <i class="fas fa-pills"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    
                    <li v-if="hasPermission('view_orders')">
                        <a href="/orders" :class="{'active': currentPage === 'orders'}">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    
                    <li v-if="hasPermission('view_stock')">
                        <a href="/inventory" :class="{'active': currentPage === 'inventory'}">
                            <i class="fas fa-box"></i>
                            <span>Inventory</span>
                        </a>
                    </li>
                    
                    <li v-if="hasPermission('view_commissions')">
                        <a href="/commissions" :class="{'active': currentPage === 'commissions'}">
                            <i class="fas fa-percentage"></i>
                            <span>Commissions</span>
                        </a>
                    </li>
                    
                    <li v-if="hasPermission('view_profits')">
                        <a href="/profits" :class="{'active': currentPage === 'profits'}">
                            <i class="fas fa-chart-line"></i>
                            <span>Profit Sharing</span>
                        </a>
                    </li>
                    
                    <li v-if="hasPermission('view_reports')">
                        <a href="/reports" :class="{'active': currentPage === 'reports'}">
                            <i class="fas fa-file-alt"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    
                    <li v-if="hasPermission('manage_settings')">
                        <a href="/settings" :class="{'active': currentPage === 'settings'}">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
                </div>
                
                <div class="header-right" v-if="isAuthenticated">
                    <!-- Notifications -->
                    <div class="notifications-dropdown dropdown">
                        <button class="btn-icon" @click="toggleNotifications">
                            <i class="fas fa-bell"></i>
                            <span v-if="unreadNotifications" class="badge">{{ unreadNotifications }}</span>
                        </button>
                        <div v-if="showNotifications" class="dropdown-menu">
                            <div class="dropdown-header">
                                <h3>Notifications</h3>
                                <button @click="markAllRead" class="btn-link">Mark all as read</button>
                            </div>
                            <div class="dropdown-body">
                                <template v-if="notifications.length">
                                    <div v-for="notification in notifications" 
                                         :key="notification.id" 
                                         class="notification-item"
                                         :class="{'unread': !notification.read_at}">
                                        <i :class="getNotificationIcon(notification.type)"></i>
                                        <div class="notification-content">
                                            <p>{{ notification.message }}</p>
                                            <small>{{ formatDate(notification.created_at) }}</small>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="notification-empty">
                                    No notifications
                                </div>
                            </div>
                            <div class="dropdown-footer">
                                <a href="/notifications">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="user-dropdown dropdown">
                        <button class="btn-icon" @click="toggleUserMenu">
                            <img :src="userAvatar" :alt="username" class="avatar">
                            <span class="username">{{ username }}</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div v-if="showUserMenu" class="dropdown-menu">
                            <a href="/profile">
                                <i class="fas fa-user"></i>
                                Profile
                            </a>
                            <a href="#" @click="changePassword">
                                <i class="fas fa-key"></i>
                                Change Password
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" @click="logout">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content">
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>
</body>
</html>
