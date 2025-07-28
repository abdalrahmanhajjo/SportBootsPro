<?php require_once 'header.php'; ?>

<!-- Page Content -->
<div class="page-content">
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Help Center</span>
                <h1 class="section-title">Frequently Asked Questions</h1>
                <p class="section-subtitle">
                    Find answers to common questions about our products and services
                </p>
            </div>

            <div class="faq-container">
                <div class="faq-category">
                    <h3>Ordering & Shipping</h3>
                    <div class="faq-item">
                        <button class="faq-question">How long does shipping take?<span>+</span></button>
                        <div class="faq-answer">
                            <p>We offer free worldwide shipping with an estimated delivery time of 5-7 business days for most countries. Some remote locations may take longer. You'll receive a tracking number once your order ships.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">Can I change or cancel my order?<span>+</span></button>
                        <div class="faq-answer">
                            <p>You can request changes or cancellation within 1 hour of placing your order by contacting our customer service. After that, we process orders quickly to ensure fast delivery, so changes may not be possible.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">Do you ship internationally?<span>+</span></button>
                        <div class="faq-answer">
                            <p>Yes, we ship to most countries worldwide. Shipping costs and delivery times vary by location. All international orders are subject to customs fees and import taxes which are the responsibility of the customer.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-category">
                    <h3>Returns & Exchanges</h3>
                    <div class="faq-item">
                        <button class="faq-question">What is your return policy?<span>+</span></button>
                        <div class="faq-answer">
                            <p>We offer a 30-day return policy for unworn, undamaged items with original packaging. Simply contact us to initiate a return. Return shipping is free for domestic returns.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">How do I exchange for a different size?<span>+</span></button>
                        <div class="faq-answer">
                            <p>To exchange for a different size, please return the original item following our return process and place a new order for the desired size. We'll process your refund once we receive the returned item.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">How long does it take to process a return?<span>+</span></button>
                        <div class="faq-answer">
                            <p>Returns are typically processed within 3-5 business days after we receive your package. You'll receive an email confirmation once your refund has been issued. Refunds may take additional time to appear on your account depending on your payment method.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-category">
                    <h3>Product Information</h3>
                    <div class="faq-item">
                        <button class="faq-question">How do I choose the right size?<span>+</span></button>
                        <div class="faq-answer">
                            <p>We provide detailed size charts for each product on its product page. For sport-specific footwear, we recommend going with your standard size unless noted otherwise in the product description.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">Are your products suitable for professional athletes?<span>+</span></button>
                        <div class="faq-answer">
                            <p>Absolutely! SportBoots Pro is trusted by elite athletes worldwide. Our footwear is engineered to meet the demands of professional competition while providing the comfort and durability needed for training.</p>
                        </div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question">How do I care for my SportBoots Pro footwear?<span>+</span></button>
                        <div class="faq-answer">
                            <p>We recommend cleaning with a soft brush and mild soap, air drying away from direct heat. Avoid machine washing. For specific care instructions, check the tag inside your shoes or our product care guide.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="faq-support">
                <p>Still have questions? Our customer service team is happy to help.</p>
                <a href="contact.php" class="btn btn-primary">Contact Support</a>
            </div>
        </div>
    </section>
</div>

<script>
// FAQ accordion functionality
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const answer = question.nextElementSibling;
        const isOpen = answer.style.maxHeight;
        
        // Close all other answers
        document.querySelectorAll('.faq-answer').forEach(a => {
            if (a !== answer) {
                a.style.maxHeight = null;
                a.previousElementSibling.querySelector('span').textContent = '+';
            }
        });
        
        // Toggle current answer
        if (isOpen) {
            answer.style.maxHeight = null;
            question.querySelector('span').textContent = '+';
        } else {
            answer.style.maxHeight = answer.scrollHeight + 'px';
            question.querySelector('span').textContent = '−';
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>