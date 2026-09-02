<?php
/**
 * Contact Us Page
 * Little Steps Childcare Platform
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';

$pageTitle = 'Contact Us';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real application, you would send an email here
    // and save the message to the database
    $success = true;
    setFlashMessage('success', 'Your message has been sent successfully. We will get back to you soon!');
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--baby-pink); padding: var(--space-3xl) 0;">
    <div class="container text-center">
        <h1 style="color: var(--dark-pink); margin-bottom: var(--space-md);">Get in Touch</h1>
        <p style="font-size: 1.125rem; color: var(--dark-gray); max-width: 700px; margin: 0 auto;">
            Have questions about our platform? Need help finding the right childcare? We're here to help.
        </p>
    </div>
</div>

<div class="container" style="padding: var(--space-3xl) 0;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3xl);">
        
        <!-- Contact Info -->
        <div>
            <h3 style="color: var(--main-pink); margin-bottom: var(--space-lg);">Contact Information</h3>
            
            <div style="display: flex; gap: var(--space-md); margin-bottom: var(--space-lg);">
                <div style="width: 48px; height: 48px; background: var(--light-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div>
                    <h4 style="margin-bottom: 4px; font-size: 16px;">Our Office</h4>
                    <p style="color: var(--medium-gray); font-size: 14px;">123 Childcare Ave, Tech City, State 12345</p>
                </div>
            </div>
            
            <div style="display: flex; gap: var(--space-md); margin-bottom: var(--space-lg);">
                <div style="width: 48px; height: 48px; background: var(--light-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div>
                    <h4 style="margin-bottom: 4px; font-size: 16px;">Email Us</h4>
                    <p style="color: var(--medium-gray); font-size: 14px;">support@littlesteps.com<br>partners@littlesteps.com</p>
                </div>
            </div>
            
            <div style="display: flex; gap: var(--space-md);">
                <div style="width: 48px; height: 48px; background: var(--light-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <i class="fas fa-phone"></i>
                </div>
                <div>
                    <h4 style="margin-bottom: 4px; font-size: 16px;">Call Us</h4>
                    <p style="color: var(--medium-gray); font-size: 14px;">+1 (234) 567-890<br>Mon-Fri, 9am - 6pm</p>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="card" style="margin: 0; box-shadow: var(--shadow-lg);">
            <h3 style="color: var(--dark-pink); margin-bottom: var(--space-md);">Send a Message</h3>
            
            <form method="POST" action="contact.php" class="needs-validation">
                <?php csrfField(); ?>
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <select name="subject" class="form-control" required>
                        <option value="">Select a subject...</option>
                        <option value="parent_support">Parent Support</option>
                        <option value="provider_support">Provider Support</option>
                        <option value="billing">Billing Question</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
        
    </div>
</div>

<style>
    @media (max-width: 767px) {
        .container > div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
