<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="footer-content">
      <div class="footer-brand">
        <div class="footer-logo" onclick="window.location.href='index.php'">
          <div class="nav-logo-icon">👟</div>
          <span>SportBoots Pro</span>
        </div>
        <p class="footer-description">
          Lebanon’s #1 online store for all sportswear and athletic footwear. <br>
          We deliver nationwide with fast shipping, trusted service, and unbeatable comfort. <br>
          Contact us via WhatsApp: 
          <a href="https://wa.me/96176536462" class="text-light text-decoration-underline">+961 76 536 462</a>
        </p>
        <div class="footer-social">
          <!-- Social Icons -->
          <a class="social-link" href="#"><i class="fab fa-facebook-f"></i></a>
          <a class="social-link" href="#"><i class="fab fa-instagram"></i></a>
          <a class="social-link" href="#"><i class="fab fa-tiktok"></i></a>
          <a class="social-link" href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>

      <div class="footer-section">
        <h4>Shop Categories</h4>
        <a class="footer-link" href="shop.php?category=football">Football Gear</a>
        <a class="footer-link" href="shop.php?category=basketball">Basketball Apparel</a>
        <a class="footer-link" href="shop.php?category=running">Running Wear</a>
        <a class="footer-link" href="shop.php?category=gym">Gym & Training</a>
        <a class="footer-link" href="shop.php?category=accessories">Accessories</a>
      </div>

      <div class="footer-section">
        <h4>Company</h4>
        <a class="footer-link" href="about.php">About Us</a>
        <a class="footer-link" href="technology.php">Technology</a>
        <a class="footer-link" href="careers.php">Careers</a>
        <a class="footer-link" href="contact.php">Media & Press</a>
      </div>

      <div class="footer-section">
        <h4>Support</h4>
        <a class="footer-link" href="contact.php">Contact Us</a>
        <a class="footer-link" href="size-guide.php">Size Guide</a>
        <a class="footer-link" href="shipping.php">Shipping & Delivery</a>
        <a class="footer-link" href="returns.php">Returns & Exchanges</a>
        <a class="footer-link" href="faq.php">FAQs</a>
      </div>
    </div>

    <div class="footer-bottom mt-4 pt-3 border-top">
      <p class="text-muted mb-0">
        © <?= date('Y') ?> SportBoots Pro — All Sportswear & Footwear in Lebanon. All rights reserved.
      </p>
      <div class="footer-payments d-flex gap-2 mt-2">
        <span class="payment-icon">💳</span>
        <span class="payment-icon">💵</span>
        <span class="payment-icon">🚚</span>
        <span class="payment-icon">🔒</span>
      </div>
    </div>
  </div>
</footer>

    <script>
        // Modern SPA Navigation System
        let currentPage = '<?= basename($_SERVER['PHP_SELF'], '.php') ?>';

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navMenu = document.querySelector('.nav-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenuBtn.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Navigation scroll effect
        const nav = document.getElementById('nav');
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Loader
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.getElementById('loader').classList.add('hidden');
            }, 1500);
        });

        // Quantity selector in product page
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let value = parseInt(input.value);
                
                if (this.classList.contains('minus')) {
                    value = Math.max(value - 1, 1);
                } else {
                    value = Math.min(value + 1, parseInt(input.max));
                }
                
                input.value = value;
            });
        });

        // Add hover effects to product cards
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });

        console.log('SportBoots Pro - Multi-page SPA initialized');
    </script>
</body>
</html>