<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartShop | Storefront & Inventory</title>

    <link rel="stylesheet" href="Assets/css/style.css">

    <link rel="stylesheet" href="Assets/fontawesome-free-7.3.1-web/css/all.min.css">
<link rel="stylesheet" href="Assets/css/home.css">
</head>

<body>

    <header class="header">

        <div class="logo">
            <div class="imge">
                <img src="Assets/Image/WhatsApp Image 2026-08-13 at 12.13.18 (21).jpeg" alt="logo">
            </div>

            <div class="ltext">
                <h2>Smart<span>Shop</span></h2>
                <p class="pp">Your online shopping partner</p>
            </div>
        </div>

        <div class="search">
            <input type="text" placeholder="Search for product">
            <button>
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>

        <div class="cart">
            <i class="fa-solid fa-cart-plus"></i>
            My cart Tsh 0
        </div>

    </header>


    <nav class="navbar">
        <ul>
            <li><a href=""><i class="fa-solid fa-house"></i></a></li>
            <li><a href="">Home</a></li>
            <li><a href="">Electronics</a></li>
            <li><a href="">Clothing</a></li>
            <li><a href="">Shoes</a></li>
            <li><a href="">Accessories</a></li>
            <li><a href="">Contact</a></li>
        </ul>
    </nav>

    <section class="inventory-shortcuts" aria-label="Inventory shortcuts">
        <div><strong><i class="fa-solid fa-boxes-stacked"></i> Inventory workspace</strong><span>Manage products, prices and stock levels.</span></div>
        <a href="products.php">Manage Products <i class="fa-solid fa-arrow-right"></i></a>
        <a href="sales.php">Sales Management <i class="fa-solid fa-cash-register"></i></a>
        <a href="stock_alerts.php">View Stock Alerts <i class="fa-solid fa-bell"></i></a>
        <a href="reporting.php">Reporting Dashboard <i class="fa-solid fa-chart-line"></i></a>
    </section>


    <section class="hero">

        <div class="text">
            <h1>Welcome to <span>SmartShop</span></h1>
            <p>Shop quality products at affordable prices</p>

            <button>
                <a href="">Shop Now</a>
            </button>
        </div>

    </section>


    <section class="product" id="products">

        <h2>Featured Products</h2>

        <div class="pro-cont">

            <div class="cat">
                <img src="Assets/Image/Iphone.jpeg" alt="Smartphone">
                <h3>Smartphone</h3>
                <p>Latest smartphone with amazing features</p>
                <h4>Tsh 450000</h4>

                <button>
                    <i class="fa-solid fa-cart-shopping"></i>
                    Add to cart
                </button>
            </div>


            <div class="cat">
                <img src="Assets/Image/Laptop.jpeg" alt="Laptop">
                <h3>Laptop</h3>
                <p>High performance laptop for work and play</p>
                <h4>Tsh 1500000</h4>

                <button>
                    <i class="fa-solid fa-cart-shopping"></i>
                    Add to cart
                </button>
            </div>


            <div class="cat">
                <img src="Assets/Image/T-shirt.jpeg" alt="T-shirt">
                <h3>T-shirt</h3>
                <p>Comfortable cotton t-shirt for everyday</p>
                <h4>Tsh 35000</h4>

                <button>
                    <i class="fa-solid fa-cart-shopping"></i>
                    Add to cart
                </button>
            </div>


            <div class="cat">
                <img src="Assets/Image/Snekears.jpeg" alt="Sneakers">
                <h3>Sneakers</h3>
                <p>Stylish and comfortable sneakers</p>
                <h4>Tsh 80000</h4>

                <button>
                    <i class="fa-solid fa-cart-shopping"></i>
                    Add to cart
                </button>
            </div>

        </div>

    </section>


    <section class="features">

        <div class="feature-item">
            <div class="image">
                <i class="fa-solid fa-truck-fast"></i>
            </div>

            <div>
                <h5>Free Delivery</h5>
                <p>On the orders over Tsh 200000</p>
            </div>
        </div>


        <div class="feature-item">
            <div class="image">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div>
                <h5>Secure payment</h5>
                <p>100% secure payment</p>
            </div>
        </div>


        <div class="feature-item">
            <div class="image">
                <i class="fa-solid fa-reply"></i>
            </div>

            <div>
                <h5>Easy Returns</h5>
                <p>30 days return policy</p>
            </div>
        </div>


        <div class="feature-item">
            <div class="image">
                <i class="fa-regular fa-headset"></i>
            </div>

            <div>
                <h5>24/7 support</h5>
                <p>Dedicated support</p>
            </div>
        </div>

    </section>


    <footer class="foo" id="contact">

        <p>
            <i class="fa-regular fa-copyright"></i>
            2026 SmartShop |
            Email: info@smartshop.com |
            Tell: +255 699092041
        </p>

        <div class="media">

            <div class="image">
                <i class="fa-brands fa-whatsapp"></i>
            </div>

            <div class="image">
                <i class="fa-brands fa-facebook"></i>
            </div>

            <div class="image">
                <i class="fa-brands fa-instagram"></i>
            </div>

            <div class="image">
                <i class="fa-brands fa-twitter"></i>
            </div>

        </div>

    </footer>

</body>

</html>