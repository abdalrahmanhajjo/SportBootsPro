<?php require_once 'header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<!-- Page Content -->
<div class="page-content">
    <!-- Home Page -->
    <div id="home" class="page active">
        <section class="hero">
            <div class="hero-bg"></div>
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title">
                            Performance Gear for
                            <span class="text-gradient">Champions</span>
                        </h1>
                        <p class="hero-subtitle">
                            Professional athletic wear engineered with cutting-edge technology. 
                            Trusted by elite athletes worldwide for unmatched performance.
                        </p>
                        <div class="hero-cta">
                            <a class="btn btn-primary btn-large" href="shop.php">Shop Collection</a>
                            <a class="btn btn-secondary btn-large" href="technology.php">Our Technology</a>
                        </div>
                        <div class="hero-features">
                            <div class="hero-feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="var(--primary)">
                                    <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-2 15l-5-5 1.41-1.41L8 12.17l7.59-7.59L17 6l-9 9z"/>
                                </svg>
                                <span>Free Shipping Worldwide</span>
                            </div>
                            <div class="hero-feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="var(--primary)">
                                    <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-2 15l-5-5 1.41-1.41L8 12.17l7.59-7.59L17 6l-9 9z"/>
                                </svg>
                                <span>Premium Materials</span>
                            </div>
                            <div class="hero-feature">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="var(--primary)">
                                    <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-2 15l-5-5 1.41-1.41L8 12.17l7.59-7.59L17 6l-9 9z"/>
                                </svg>
                                <span>Pro Athletes Choice</span>
                            </div>
                        </div>
                    </div>
                    <div class="hero-visual">
                        <div class="hero-image-slider">
                            <div class="hero-slide active" data-category="boots">
                                <img src="https://cdn.lovellsports.com/features/splash-pages/soccer/boots/new-balance-boots-09.01.jpg" alt="Premium Athletic Boots">
                                <div class="hero-badge">NEW BOOTS COLLECTION</div>
                            </div>
                            <div class="hero-slide" data-category="tshirts">
                                <img src="https://thvnext.bing.com/th/id/OIP.C9ycnby95alnNnfdX3dsCwHaLH?cb=thvnext&rs=1&pid=ImgDetMain" alt="Performance T-Shirts">
                                <div class="hero-badge">BREATHABLE FABRICS</div>
                            </div>
                            <div class="hero-slide" data-category="shirts">
                                <img src="https://th.bing.com/th/id/R.1113ed1763469041c55d9bbdabc5c927?rik=lTHDjkNacjMghw&riu=http%3a%2f%2fwww.victorsport.com%2ffiles%2fzh_tw%2fproduct%2fmore%2f109620_0_20231205152637.jpg&ehk=xei3naoybBB2DoVleFsbNcIdo6VvKuTGL61%2bavLp0F4%3d&risl=&pid=ImgRaw&r=0" alt="Sport Shirts">
                                <div class="hero-badge">TECHNICAL SHIRTS</div>
                            </div>
                        </div>
                        <div class="hero-slider-controls">
                            <button class="slider-control prev" aria-label="Previous slide">←</button>
                            <div class="slider-dots">
                                <button class="dot active" data-slide="0" aria-label="Show boots slide"></button>
                                <button class="dot" data-slide="1" aria-label="Show t-shirts slide"></button>
                                <button class="dot" data-slide="2" aria-label="Show shirts slide"></button>
                            </div>
                            <button class="slider-control next" aria-label="Next slide">→</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Premium Collections</span>
            <h2 class="section-title">Built For Performance</h2>
            <p class="section-subtitle">
                Explore our technical apparel designed for maximum athletic output
            </p>
        </div>

        <div class="category-grid">
            <a href="shop.php?category=boots" class="category-card">
                <div class="category-image">
                    <img src="https://images.unsplash.com/photo-1612387049695-637b743f80ad?q=80&w=1988&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Performance Boots">
                </div>
                <div class="category-content">
                    <h3 class="category-title">Elite Footwear</h3>
                    <p class="category-description">Engineered for speed, agility and explosive power</p>
                    <span class="category-link">Shop Boots →</span>
                </div>
            </a>

            <a href="shop.php?category=tshirts" class="category-card">
                <div class="category-image">
                    <img src="https://images.unsplash.com/photo-1593493354159-b2fb9fe1a36c?q=80&w=2069&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Performance T-Shirts">
                </div>
                <div class="category-content">
                    <h3 class="category-title">Training Tees</h3>
                    <p class="category-description">Moisture-wicking fabrics with advanced ventilation</p>
                    <span class="category-link">Shop T-Shirts →</span>
                </div>
            </a>

            <a href="shop.php?category=shirts" class="category-card">
                <div class="category-image">
                    <img src="https://images.unsplash.com/photo-1616124619460-ff4ed8f4683c?q=80&w=1996&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Sport Shirts">
                </div>
                <div class="category-content">
                    <h3 class="category-title">Game Day Shirts</h3>
                    <p class="category-description">Lightweight, breathable and engineered for movement</p>
                    <span class="category-link">Shop Shirts →</span>
                </div>
            </a>
        </div>
    </div>
</section>

<section class="section bg-light py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="section-tag text-uppercase text-primary fw-semibold">Why Athletes Choose Us</span>
            <h2 class="section-title fw-bold display-5">Engineered Excellence</h2>
            <p class="section-subtitle text-muted fs-5">
                Every product is designed with professional-grade technology
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                    <div class="feature-icon display-4 text-primary mb-3">
                        <i class="fas fa-shoe-prints"></i>
                    </div>
                    <h3 class="feature-title h5 mb-2">Dynamic Fit</h3>
                    <p class="feature-description text-muted small">
                        Adaptive materials that move with your body for unrestricted performance
                    </p>
                    <div class="feature-applies mt-3 small text-muted">
                        <strong>Applies to:</strong> Boots, Shirts, T-Shirts
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                    <div class="feature-icon display-4 text-primary mb-3">
                        <i class="fas fa-wind"></i>
                    </div>
                    <h3 class="feature-title h5 mb-2">Advanced Ventilation</h3>
                    <p class="feature-description text-muted small">
                        Strategic airflow systems to regulate temperature during intense activity
                    </p>
                    <div class="feature-applies mt-3 small text-muted">
                        <strong>Applies to:</strong> T-Shirts, Shirts
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                    <div class="feature-icon display-4 text-primary mb-3">
                        <i class="fas fa-grip-lines-vertical"></i>
                    </div>
                    <h3 class="feature-title h5 mb-2">Precision Traction</h3>
                    <p class="feature-description text-muted small">
                        Multi-surface grip patterns for explosive starts and quick cuts
                    </p>
                    <div class="feature-applies mt-3 small text-muted">
                        <strong>Applies to:</strong> Boots
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                    <div class="feature-icon display-4 text-primary mb-3">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3 class="feature-title h5 mb-2">Moisture Control</h3>
                    <p class="feature-description text-muted small">
                        High-tech fabrics that wick sweat away 40% faster than standard materials
                    </p>
                    <div class="feature-applies mt-3 small text-muted">
                        <strong>Applies to:</strong> T-Shirts, Shirts
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



     <section class="stats py-5 bg-dark text-white">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Our Growing Impact</h2>
      <p class="text-light">Trusted by athletes across Lebanon and beyond</p>
    </div>
    <div class="row text-center stats-grid">
      <div class="col-6 col-md-3 mb-4">
        <div class="stat-item">
          <div class="stat-number" data-count="5000">0</div>
          <div class="stat-label">Athletes Reached</div>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-4">
        <div class="stat-item">
          <div class="stat-number" data-count="15">0</div>
          <div class="stat-label">Cities Covered</div>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-4">
        <div class="stat-item">
          <div class="stat-number" data-count="98">0</div>
          <div class="stat-label">Positive Reviews</div>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-4">
        <div class="stat-item">
          <div class="fs-3 text-warning">★★★★★</div>
          <div class="stat-label">Customer Satisfaction</div>
        </div>
      </div>
    </div>
  </div>
</section>


      <section class="testimonials py-5 bg-white">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag text-uppercase text-primary fw-semibold">Testimonials</span>
      <h2 class="section-title fw-bold display-5">Trusted By Champions</h2>
      <p class="text-muted">Hear what elite athletes say about our performance gear</p>
    </div>

    <div class="row g-4">
      <!-- Testimonial 1 -->
      <div class="col-md-4">
        <div class="testimonial-card bg-light p-4 rounded-4 shadow-sm h-100 d-flex flex-column justify-content-between">
          <div class="text-center">
            <div class="testimonial-icon mb-3 text-warning display-6">
              <i class="fas fa-shoe-prints"></i>
            </div>
            <p class="testimonial-text fst-italic text-muted">
              "The boots gave me unmatched traction and comfort. My speed and stability drastically improved!"
            </p>
          </div>
          <div class="text-center mt-4">
            <h5 class="fw-bold mb-1">Alex Morgan</h5>
            <small class="text-muted">Professional Soccer Player</small>
          </div>
        </div>
      </div>

      <!-- Testimonial 2 -->
      <div class="col-md-4">
        <div class="testimonial-card bg-light p-4 rounded-4 shadow-sm h-100 d-flex flex-column justify-content-between">
          <div class="text-center">
            <div class="testimonial-icon mb-3 text-primary display-6">
              <i class="fas fa-tshirt"></i>
            </div>
            <p class="testimonial-text fst-italic text-muted">
              "The training shirts are incredibly lightweight and breathable. They keep me cool during peak workouts."
            </p>
          </div>
          <div class="text-center mt-4">
            <h5 class="fw-bold mb-1">James Rodriguez</h5>
            <small class="text-muted">NBA Point Guard</small>
          </div>
        </div>
      </div>

      <!-- Testimonial 3 -->
      <div class="col-md-4">
        <div class="testimonial-card bg-light p-4 rounded-4 shadow-sm h-100 d-flex flex-column justify-content-between">
          <div class="text-center">
            <div class="testimonial-icon mb-3 text-success display-6">
              <i class="fas fa-wind"></i>
            </div>
            <p class="testimonial-text fst-italic text-muted">
              "I’ve never felt drier during a sprint. These moisture-wicking fabrics really outperform everything else."
            </p>
          </div>
          <div class="text-center mt-4">
            <h5 class="fw-bold mb-1">Sarah Johnson</h5>
            <small class="text-muted">Olympic Sprinter</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>




<style>
 /* Modern CSS Reset */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Custom Properties */
        :root {
            /* Modern Color System */
            --primary: #FF0000;
            --primary-dark: #CC0000;
            --primary-light: #FF3333;
            
            --black: #000000;
            --white: #FFFFFF;
            --gray-100: #F7F7F7;
            --gray-200: #E5E5E5;
            --gray-300: #D4D4D4;
            --gray-400: #A3A3A3;
            --gray-500: #737373;
            --gray-600: #525252;
            --gray-700: #404040;
            --gray-800: #262626;
            --gray-900: #171717;
            
            /* Typography */
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-display: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            
            /* Spacing */
            --space-xs: 0.5rem;
            --space-sm: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2rem;
            --space-xl: 3rem;
            --space-2xl: 4rem;
            --space-3xl: 6rem;
            
            /* Transitions */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        /* Base Styles */
        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }

        body {
            font-family: var(--font-sans);
            font-weight: 400;
            line-height: 1.5;
            color: var(--black);
            background: var(--white);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

/* Hero Section */
.hero {
    position: relative;
    padding: 4rem 0;
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    z-index: -1;
}

.hero-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.hero-text {
    max-width: 600px;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1.2;
    color: var(--dark);
    margin-bottom: 1.5rem;
}

.text-gradient {
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: var(--gray-600);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--primary);
    color: white;
    border: 2px solid var(--primary);
}

.btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: transparent;
    color: var(--primary);
    border: 2px solid var(--primary);
}

.btn-secondary:hover {
    background: rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.hero-features {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.hero-feature {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: var(--gray-700);
}

.hero-visual {
    position: relative;
}

.hero-image-slider {
    position: relative;
    height: 500px;
    overflow: hidden;
    border-radius: 16px;
    box-shadow: var(--shadow-xl);
}

.hero-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease;
}

.hero-slide.active {
    opacity: 1;
}

.hero-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-badge {
    position: absolute;
    top: 1.5rem;
    left: 1.5rem;
    background: var(--primary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.hero-slider-controls {
    position: absolute;
    bottom: 1.5rem;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
}

.slider-control {
    background: rgba(255, 255, 255, 0.8);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.slider-control:hover {
    background: white;
    transform: scale(1.1);
}

.slider-dots {
    display: flex;
    gap: 0.5rem;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.2s;
}

.dot.active {
    background: white;
    transform: scale(1.2);
}

/* Section Styles */
.section {
    padding: 5rem 0;
}

.section-header {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 3rem;
}

.section-tag {
    display: inline-block;
    background: var(--primary-light);
    color: white;
    padding: 0.5rem 1.25rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.125rem;
    color: var(--gray-600);
    line-height: 1.6;
}

.bg-light {
    background: var(--light);
}

/* Category Grid */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.category-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.category-image {
    height: 250px;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.05);
}

.category-content {
    padding: 1.5rem;
}

.category-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--dark);
}

.category-description {
    color: var(--gray-600);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.category-link {
    color: var(--primary);
    font-weight: 600;
    transition: color 0.2s;
}

.category-card:hover .category-link {
    color: var(--primary-dark);
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    text-align: center;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.feature-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--light);
    border-radius: 50%;
}

.feature-icon img {
    width: 40px;
    height: 40px;
}

.feature-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--dark);
}

.feature-description {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.feature-applies {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.applies-to {
    font-weight: 500;
}

.product-types {
    color: var(--primary);
    font-weight: 600;
}

/* Product Highlights */
.product-highlights {
    margin-top: 3rem;
}

.product-tabs {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.product-tab {
    padding: 0.75rem 1.5rem;
    border: none;
    background: var(--gray-100);
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.product-tab.active {
    background: var(--primary);
    color: white;
}

.product-tab:not(.active):hover {
    background: var(--gray-200);
}

.product-showcase {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.product-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--danger);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    z-index: 2;
}

.product-image {
    height: 200px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.product-info {
    padding: 1.5rem;
}

.product-category {
    display: inline-block;
    color: var(--primary);
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.product-name {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--dark);
}

.product-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 1.5rem;
}

.original-price {
    font-size: 0.875rem;
    color: var(--gray-400);
    text-decoration: line-through;
    margin-left: 0.5rem;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.btn-outline {
    background: transparent;
    color: var(--primary);
    border: 2px solid var(--primary);
}

.btn-outline:hover {
    background: rgba(59, 130, 246, 0.1);
}

/* Stats Section */
.stats {
    padding: 4rem 0;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark)));
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    text-align: center;
}

.stat-item {
    padding: 1rem;
}

.stat-number {
    display: block;
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.stat-label {
    font-size: 1.125rem;
    font-weight: 500;
    opacity: 0.9;
}

/* Testimonials */
.testimonials {
    padding: 5rem 0;
    background: var(--light);
}

.testimonial-slider {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
}

.testimonial {
    display: none;
    padding: 2rem;
}

.testimonial.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

.testimonial-content {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    position: relative;
}

.testimonial-text {
    font-size: 1.25rem;
    line-height: 1.6;
    color: var(--gray-700);
    margin-bottom: 2rem;
    font-style: italic;
    position: relative;
    padding-left: 2rem;
}

.testimonial-text::before {
    content: '"';
    position: absolute;
    left: 0;
    top: -1rem;
    font-size: 4rem;
    color: var(--gray-200);
    font-family: serif;
    line-height: 1;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.author-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-light);
}

.author-info h4 {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--dark);
}

.author-title {
    font-size: 0.875rem;
    color: var(--gray-500);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* CTA Section */
.cta {
    padding: 5rem 0;
    background: linear-gradient(135deg, #1e3a8a, #1e40af);
    color: white;
}

.cta-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr 1fr;
    box-shadow: var(--shadow-xl);
}

.cta-content {
    padding: 4rem;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark)));
    color: white;
}

.cta-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.3;
}

.cta-subtitle {
    font-size: 1.125rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.cta-actions {
    display: flex;
    gap: 1rem;
}

.cta-image {
    position: relative;
}

.cta-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .hero-content {
        grid-template-columns: 1fr;
    }
    
    .hero-text {
        max-width: 100%;
        text-align: center;
    }
    
    .hero-cta {
        justify-content: center;
    }
    
    .hero-features {
        align-items: center;
    }
    
    .hero-visual {
        order: -1;
        margin-bottom: 2rem;
    }
    
    .cta-card {
        grid-template-columns: 1fr;
    }
    
    .cta-image {
        height: 300px;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.125rem;
    }
    
    .hero-cta {
        flex-direction: column;
    }
    
    .section {
        padding: 3rem 0;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .cta-content {
        padding: 2rem;
    }
    
    .cta-title {
        font-size: 1.75rem;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.75rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .product-tabs {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cta-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero Slider
    const heroSlides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    let currentSlide = 0;
    
    function showSlide(index) {
        heroSlides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        heroSlides[index].classList.add('active');
        dots[index].classList.add('active');
        currentSlide = index;
    }
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => showSlide(index));
    });
    
    document.querySelector('.slider-control.next').addEventListener('click', () => {
        let nextSlide = (currentSlide + 1) % heroSlides.length;
        showSlide(nextSlide);
    });
    
    document.querySelector('.slider-control.prev').addEventListener('click', () => {
        let prevSlide = (currentSlide - 1 + heroSlides.length) % heroSlides.length;
        showSlide(prevSlide);
    });
    
    // Auto-rotate slides every 5 seconds
    setInterval(() => {
        let nextSlide = (currentSlide + 1) % heroSlides.length;
        showSlide(nextSlide);
    }, 5000);
    
    // Product Tabs Filtering
    const productTabs = document.querySelectorAll('.product-tab');
    const productCards = document.querySelectorAll('.product-card');
    
    productTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            productTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            
            productCards.forEach(card => {
                if (category === 'all' || card.dataset.type === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
document.addEventListener("DOMContentLoaded", () => {
  const counters = document.querySelectorAll(".stat-number");
  const speed = 80; // Lower = faster

  counters.forEach(counter => {
    const updateCount = () => {
      const target = +counter.getAttribute("data-count");
      const count = +counter.innerText;
      const inc = Math.ceil(target / speed);

      if (count < target) {
        counter.innerText = count + inc;
        setTimeout(updateCount, 20);
      } else {
        counter.innerText = target.toLocaleString();
      }
    };

    updateCount();
  });
});
</script>

<?php require_once 'footer.php'; ?>