# E-commerce Platform

This is a comprehensive e-commerce platform designed to provide a seamless shopping experience. It includes features for product browsing, cart management, user authentication, and order processing.

## Features

- **Product Browsing**: Users can browse products by category, search for specific items, and view detailed product information.
- **Cart Management**: Users can add products to their cart, update quantities, and remove items. The cart is session-based for non-logged-in users and database-backed for logged-in users.
- **User Authentication**: Users can register, log in, and log out. The platform supports "Remember Me" functionality for persistent logins.
- **Order Processing**: Users can proceed to checkout, confirm orders, and view order details.
- **Admin Management**: Admins can manage product categories, edit products, and view order details.

## Project Structure

- **admin/**: Contains admin-specific pages for managing categories and products.
- **assets/**: Contains static assets like CSS and JavaScript files.
- **includes/**: Contains configuration, function definitions, and auto-login logic.
- ***.php**: Various PHP files for handling different functionalities like login, logout, cart actions, and order processing.

## Database Structure

- **users**: Stores user information including login credentials and remember tokens.
- **products**: Stores product details.
- **categories**: Stores product categories.
- **orders**: Stores order details.
- **cart**: Stores cart items for logged-in users.

## Setup Instructions

1. Clone the repository.
2. Set up the database with the provided schema.
3. Configure the database connection in `includes/config.php`.
4. Run the application on a local server.

## Security Considerations

- Passwords are securely hashed.
- Tokens are used for "Remember Me" functionality and are securely stored.
- SQL statements are prepared to prevent SQL injection.

## License

This project is licensed under the MIT License.
