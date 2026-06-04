# KrishiConnect Database Documentation

## 📊 Database Overview

KrishiConnect is an agricultural microfinance platform that connects farmers with buyers while providing financial services through loans. This database is designed to handle:

- **Marketplace Operations**: Product listing, ordering, and payment management
- **Microfinance Services**: Loan applications, disbursements, and repayments
- **User Management**: Farmers, buyers, finance officers, and administrators
- **Quality Assurance**: Reviews, ratings, KYC verification, and dispute resolution
- **Reporting & Analytics**: Sales reports, financial analytics, and business intelligence

---

## 📁 SQL Files Description

### 1. **schema.sql** - Core Database Schema
The main schema file containing all table definitions:

**Core Tables:**
- `users` - All system users with role-based access
- `farmer_profiles` - Farmer-specific information including KYC status
- `buyer_profiles` - Buyer company details
- `user_settings` - User preferences and notification settings
- `user_payment_methods` - Multiple payment methods per user
- `finance_profiles` - Finance officer details

**Product & Inventory Management:**
- `categories` - Product categories with slugs
- `products` - Farmer's products for sale
- `product_images` - Multiple images per product
- `product_batch` - Track product batches with expiry dates
- `market_prices` - Regional market price tracking

**Shopping & Orders:**
- `carts` - Shopping carts with status tracking
- `cart_items` - Individual items in carts
- `orders` - Complete order information
- `order_items` - Line items in orders with farmer information
- `payments` - Payment transactions
- `refunds` - Refund requests and processing

**Microfinance:**
- `loan_products` - Different loan product offerings
- `loan_applications` - Farmer loan applications
- `loans` - Approved loans with disbursement tracking
- `loan_payments` - Installment schedule and payment history
- `loan_disbursements` - Tracking disbursement schedule
- `loan_documents` - Agreement and collateral documents
- `loan_guarantors` - Guarantor information

**Reviews & Quality:**
- `reviews` - Product and farmer ratings/reviews
- `review_responses` - Seller responses to reviews

**Disputes & Resolution:**
- `disputes` - Order and loan disputes
- `dispute_messages` - Communication during disputes
- `dispute_documents` - Evidence documents

**Communication:**
- `conversations` - Message threads
- `conversation_participants` - Users in conversations
- `messages` - Individual messages with read status
- `notifications` - System notifications to users
- `notification_templates` - Email/SMS notification templates

**Content & Management:**
- `blog_posts` - Blog articles and news
- `blog_comments` - Comments on blog posts
- `faq` - Frequently asked questions

**Audit & Analytics:**
- `activity_logs` - User activity tracking
- `admin_logs` - Admin action audit trail
- `sales_reports` - Monthly farmer sales reports
- `financial_reports` - Monthly financial analytics

---

### 2. **queries.sql** - Complex Queries with Joins & Subqueries

This file contains 10 categories of essential queries:

#### **1. Farmer Dashboard Queries**
- Total sales with product details and revenue breakdown
- Monthly sales trends analysis
- Loan repayment status and schedule

#### **2. Buyer Dashboard Queries**
- Order history with farmer details
- Favorite farmers and purchase patterns
- Spending analysis

#### **3. Finance Officer Queries**
- Loan portfolio analysis
- Overdue payment tracking
- Officer performance metrics

#### **4. Marketplace & Product Queries**
- Trending products by sales volume
- Product comparison with market prices
- Category performance analysis

#### **5. Review & Rating Queries**
- Farmer reputation metrics
- Review distribution analysis
- Recent feedback summary

#### **6. Dispute Resolution Queries**
- Dispute statistics and resolution rates
- Open disputes requiring attention
- Average resolution time analysis

#### **7. Payment & Financial Queries**
- Payment method analysis
- Daily revenue trends
- Transaction success rates

#### **8. User Activity & Engagement**
- Active farmers with growth metrics
- Buyer engagement scoring
- Platform activity trends

#### **9. Inventory & Stock**
- Low stock alerts
- Reorder recommendations
- Stock movement analysis

#### **10. KYC & Compliance**
- Farmer verification status
- Document compliance tracking
- Regulatory compliance dashboard

---

### 3. **views.sql** - Pre-built Views

Database views for common reporting needs:

**Farmer Views:**
- `vw_farmer_sales_summary` - Complete sales overview
- `vw_farmer_loan_status` - Loan details with repayment status

**Buyer Views:**
- `vw_buyer_purchase_summary` - Purchase history and metrics
- `vw_product_market_comparison` - Farmer prices vs market prices

**Analytics Views:**
- `vw_category_performance` - Sales by category
- `vw_daily_sales_metrics` - Daily KPIs
- `vw_payment_default_risk` - Loan default risk assessment
- `vw_user_compliance_status` - KYC and compliance status
- `vw_top_performers` - Top farmers and buyers

**Finance Views:**
- `vw_loan_portfolio_summary` - Portfolio overview
- `vw_monthly_finance_report` - Monthly financial summary

**Usage Example:**
```sql
SELECT * FROM vw_farmer_sales_summary WHERE farmer_id = 5;
SELECT * FROM vw_daily_sales_metrics WHERE sale_date >= '2026-01-01';
```

---

### 4. **stored_procedures.sql** - Business Logic

Automated procedures for complex operations:

**Order Management:**
- `sp_create_order_from_cart()` - Create order from cart with validation
- `sp_deliver_order()` - Mark order as delivered and trigger reviews

**Loan Management:**
- `sp_approve_loan_application()` - Approve loan with payment schedule generation
- `sp_reject_loan_application()` - Reject loan with notification
- `sp_process_loan_payment()` - Process payment with penalty calculation

**Reporting:**
- `sp_generate_monthly_reports()` - Generate sales and financial reports

**Compliance:**
- `sp_update_farmer_kyc()` - Update KYC status with audit logging

**Benefits:**
- Transaction safety with ROLLBACK on errors
- Automated notification creation
- Business logic consistency
- Audit trail creation

**Usage Example:**
```sql
CALL sp_create_order_from_cart(
    p_buyer_id := 123,
    p_cart_id := 456,
    p_shipping_address := '123 Farm Lane',
    p_shipping_phone := '1234567890',
    p_payment_method := 'bKash',
    @order_id := 0,
    @message := ''
);
```

---

### 5. **indexes.sql** - Performance Optimization

Comprehensive indexing strategy:

**Index Types:**
- Single column indexes on frequently queried fields
- Composite indexes on common filter combinations
- Foreign key indexes for JOIN operations
- Fulltext indexes for search functionality

**Coverage:**
- User & authentication queries
- Product searches and filtering
- Order and payment lookups
- Loan application and payment queries
- Review and rating aggregations
- Message and notification filtering

**Expected Performance Improvements:**
- 10-100x faster dashboard queries
- Optimized full-text search
- Efficient aggregation queries
- Fast sorted result sets

---

## 🔑 Key Database Features

### 1. **Multi-Role System**
- **Farmer**: Sell products, apply for loans, manage KYC
- **Buyer**: Purchase products, provide reviews
- **Finance Officer**: Approve loans, track repayments
- **Admin**: Manage users, handle disputes, generate reports

### 2. **Comprehensive KYC System**
- Document verification
- Certification tracking
- Status management with audit trail

### 3. **Advanced Loan Management**
- Multiple loan products with different terms
- Automatic payment schedule generation
- Penalty calculation for late payments
- Risk assessment scoring
- Guarantor tracking

### 4. **Order & Payment Tracking**
- Complete order lifecycle
- Multiple payment methods
- Refund management
- Transaction audit trail

### 5. **Quality Assurance**
- 5-star rating system
- Detailed review comments
- Seller responses to reviews
- Review moderation

### 6. **Dispute Resolution**
- Severity-based categorization
- Communication thread tracking
- Evidence documentation
- Resolution method tracking

### 7. **Business Analytics**
- Daily sales metrics
- Monthly reports by farmer
- Financial analytics
- Default risk assessment

---

## 🔗 Important Relationships

```
Users (1) ─── (1) Farmer_Profiles
         └─── (1) Buyer_Profiles
         └─── (1) Finance_Profiles
         └─── (1) User_Settings
         └─── (M) User_Payment_Methods
         └─── (M) Products
         └─── (M) Orders
         └─── (M) Loan_Applications
         └─── (M) Loans

Products (1) ─── (M) Order_Items
         ├─── (M) Reviews
         ├─── (M) Product_Images
         ├─── (M) Product_Batch
         └─── (M) Wishlist_Items

Orders (1) ─── (M) Order_Items
       ├─── (M) Payments
       ├─── (M) Reviews
       ├─── (M) Disputes
       └─── (1) Refunds

Loans (1) ─── (M) Loan_Payments
      ├─── (M) Loan_Disbursements
      ├─── (M) Loan_Guarantors
      └─── (M) Loan_Documents
```

---

## 📊 Data Aggregation Examples

### Farmer Performance Dashboard
```sql
SELECT * FROM vw_farmer_sales_summary 
WHERE farmer_id = 5
AND total_orders > 0;
```

### Loan Default Risk Alert
```sql
SELECT * FROM vw_payment_default_risk 
WHERE risk_level IN ('High', 'Critical')
ORDER BY days_overdue DESC;
```

### Market Analysis
```sql
SELECT p.name, p.price, mp.price, 
       ROUND((mp.price - p.price) / p.price * 100, 2) as margin_pct
FROM vw_product_market_comparison p
LEFT JOIN market_prices mp
WHERE p.category = 'Vegetables';
```

---

## 🛡️ Data Integrity Features

1. **Foreign Key Constraints**: Referential integrity on all relationships
2. **Cascading Deletes**: Proper cleanup when parent records are deleted
3. **Unique Constraints**: Prevent duplicate critical data
4. **Check Constraints**: Validate rating ranges (1-5)
5. **Audit Logging**: Track all administrative actions
6. **Activity Logging**: Monitor user activities

---

## 🔍 Search Optimization

**Fulltext Search Indexes:**
- Products (name, variety, description)
- Blog posts (title, excerpt, content)
- Disputes (title, description)

**Example:**
```sql
SELECT * FROM products 
WHERE MATCH(name, variety, description) AGAINST('organic tomato' IN BOOLEAN MODE);
```

---

## 📈 Expected Query Performance

With proper indexes in place:

| Query Type | Typical Time |
|-----------|-------------|
| Single user lookup | < 1ms |
| Dashboard aggregations | 50-200ms |
| Monthly reports | 200-500ms |
| Full-text search | 100-300ms |
| Complex analytics | 500-2000ms |

---

## 🚀 Installation Instructions

1. **Create Database:**
```sql
CREATE DATABASE KrishiConnect;
USE KrishiConnect;
```

2. **Load Schema:**
```sql
SOURCE schema.sql;
SOURCE indexes.sql;
SOURCE stored_procedures.sql;
SOURCE views.sql;
```

3. **Verify Installation:**
```sql
SHOW TABLES;
SHOW VIEWS;
```

---

## 📝 Maintenance Tasks

1. **Monthly**: Run `sp_generate_monthly_reports()`
2. **Weekly**: Monitor `activity_logs` for security
3. **Daily**: Check `vw_payment_default_risk` for overdue loans
4. **Quarterly**: Analyze `sales_reports` for trends

---

## 🔐 Security Considerations

- Use parameterized queries to prevent SQL injection
- Hash passwords with bcrypt or similar
- Implement row-level security for sensitive data
- Audit all admin actions
- Regular backups of database
- Encrypt sensitive payment information

---

## 📞 Support & Questions

For database schema questions or optimization requests, refer to the queries in:
- `queries.sql` - Complex query patterns
- `views.sql` - Pre-built reporting views
- `stored_procedures.sql` - Business logic automation

---

**Last Updated:** June 2026
**Version:** 1.0
**Database Engine:** MySQL 5.7+
