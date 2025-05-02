
# 💊 Pharmaceutical POS & Inventory Management System

A fullstack modular Point of Sale (POS) and pharmaceutical inventory management system built with **pure PHP (no framework)** and **MySQL**, using an **MVC-like folder structure**. This system is designed for businesses with multiple investors, supporting stocked and by-order medicines, commission rules, profit sharing, installment payments, and modern reporting. Fully responsive for desktop and mobile.

---

## 🚀 Features

- Modular MVC-like architecture
- Responsive frontend (mobile & desktop)
- Role-based access: **Admin** & **Sales**
- Product categorization using **BPOM reference data**
- Support for **stocked** and **by-order** medicines
- Price adjustment with discount validation and reason logs
- Commission system:
  - Global rates
  - Category-based rates
  - Product-specific rates
- Profit sharing based on investor capital percentage
- Handles **cash** and **installment** payments (suppliers/customers)
- Integrated **Email & WhatsApp notifications**
- Detailed reports, audit logs & business insights

---

## 🧱 Tech Stack

| Layer       | Technology      |
|-------------|-----------------|
| Backend     | PHP (no framework) |
| Database    | MySQL           |
| Frontend    | HTML, CSS, JavaScript |
| Architecture| Custom MVC-like |

---

## 🗂️ Folder Structure

```
/app
  /controllers
  /models
  /views
/assets
  /css
  /js
/config
/database
  /migrations
  /seeders
/public
  index.php
```

---

## 📦 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/pharma-pos.git
   cd pharma-pos
   ```

2. **Set up your database**
   - Create a MySQL database
   - Import the SQL file from `/database/`
   - Configure `/config/database.php` with your DB credentials

3. **Run the app**
   - Use a local webserver (e.g., XAMPP, Laragon)
   - Place the project in `htdocs` or `www`
   - Open `http://localhost/pharma-pos/public` in your browser

---

## 🔐 User Roles

### 👑 Admin
- Full system access
- Manage users, roles, commissions, product pricing
- Distribute investor profit
- Access all reports

### 🧾 Sales
- Create orders
- View assigned reports
- Suggest new products (manual entry for by-order)

---

## 📊 Reporting & Analytics

- Revenue reports (daily/weekly/monthly/yearly)
- Commission summaries by user/product
- Investor profit reports
- Price adjustment logs
- Product & market insights

---

## 📬 Notifications

Integrated with **Email & WhatsApp API** for:
- New order alerts
- Delays or stock issues
- Shipping & order completion updates

---

## 📈 Insight Features

- Best-selling products & categories
- Least-performing products
- Market response by customer type (e.g., clinic, pharmacy)
- Graphs & trends for better decisions

---

## 🤝 Contribution

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

---

## 📄 License

This project is licensed under the MIT License.

---

## 📫 Contact

For support or inquiries:

- Email: your.email@example.com
- GitHub: [your-username](https://github.com/your-username)
