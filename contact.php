<?php
// MUST be the very first line with NO whitespace before
ob_start();
session_start();

require_once 'header.php';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($message)) {
        $errors['message'] = 'Message is required';
    }
    
    // If no errors, redirect to WhatsApp
    if (empty($errors)) {
        $whatsapp_message = "📧 *New Contact Message* 📧\n\n";
        $whatsapp_message .= "👤 *Name:* $name\n";
        $whatsapp_message .= "📧 *Email:* $email\n";
        $whatsapp_message .= "📌 *Subject:* " . ($subject ?: "No subject") . "\n";
        $whatsapp_message .= "💬 *Message:*\n$message\n\n";
        $whatsapp_message .= "Sent from SportBoots Pro website";
        
        $encoded_message = rawurlencode($whatsapp_message);
        $whatsapp_url = "https://wa.me/96176536462?text=$encoded_message";
        
        // Clear output buffer before redirect
        ob_end_clean();
        header("Location: $whatsapp_url");
        exit();
    }
}

// Flush output buffer before HTML
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SportBoots Pro</title>
    <style>
    /* Mobile-First Styles */
    .contact-container {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .contact-form, .contact-info {
        width: 100%;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .form-input, .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
    
    .form-textarea {
        min-height: 120px;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    .has-error .form-input,
    .has-error .form-textarea {
        border-color: #dc3545;
    }
    
    .btn-large {
        width: 100%;
        padding: 1rem;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
    }
    
    .contact-info {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
    }
    
    .info-item {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: flex-start;
    }
    
    .info-icon {
        font-size: 1.25rem;
        margin-top: 0.25rem;
    }
    
    .info-text h4 {
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }
    
    .info-text p {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    /* Tablet and Desktop Styles */
    @media (min-width: 768px) {
        .contact-container {
            flex-direction: row;
        }
        
        .contact-form {
            flex: 2;
        }
        
        .contact-info {
            flex: 1;
        }
        
        .btn-large {
            width: auto;
            padding: 0.75rem 2rem;
        }
    }
    </style>
</head>
<body>
<!-- Page Content -->
<div class="page-content">
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Get in Touch</span>
                <h1 class="section-title">Contact Us</h1>
                <p class="section-subtitle">
                    We're here to help you find the perfect athletic footwear
                </p>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message']['type'] ?>">
                    <?= $_SESSION['message']['text'] ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="contact-container">
                <form class="contact-form" method="post" action="contact.php">
                    <div class="form-group <?= isset($errors['name']) ? 'has-error' : '' ?>">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-input" 
                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <span class="error-message"><?= $errors['name'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" 
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message"><?= $errors['email'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-input" 
                               value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>">
                    </div>
                    <div class="form-group <?= isset($errors['message']) ? 'has-error' : '' ?>">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-textarea" required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <span class="error-message"><?= $errors['message'] ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-large">Send via WhatsApp</button>
                </form>

                <div class="contact-info">
                    <h3>Our Contact Details</h3>
                    <div class="info-item">
                        <div class="info-icon">📱</div>
                        <div class="info-text">
                            <h4>WhatsApp</h4>
                            <p>+961 76 536 462<br>24/7 Support</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">✉️</div>
                        <div class="info-text">
                            <h4>Email</h4>
                            <p>support@sportbootspro.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">🌐</div>
                        <div class="info-text">
                            <h4>Social Media</h4>
                            <p>@sportbootspro</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">⏰</div>
                        <div class="info-text">
                            <h4>Business Hours</h4>
                            <p>Monday-Sunday<br>9:00 AM - 9:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php 
// No whitespace after this closing tag
require_once 'footer.php';