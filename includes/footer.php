<?php
/**
 * Shared Public Footer
 * Little Steps Childcare Platform
 */
?>
    <footer style="background: var(--near-black); color: var(--white); padding: 80px 0 40px; margin-top: auto;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 60px; margin-bottom: 60px;">
                
                <!-- About Column -->
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 24px;">
                        <div style="color: var(--main-pink); font-size: 28px; transform: rotate(-15deg);">
                            <i class="fas fa-shoe-prints"></i>
                        </div>
                        <span style="font-weight: 700; font-size: 1.5rem; color: var(--white); letter-spacing: -0.5px;">Little <span style="color: var(--main-pink);">Steps</span></span>
                    </div>
                    <p style="color: #BDBDBD; font-size: 15px; line-height: 1.6;">
                        Your trusted platform for finding safe, verified, and flexible 24x7 childcare options. We combine safety, flexibility, and love.
                    </p>
                    <div style="display: flex; gap: 16px; margin-top: 24px;">
                        <a href="#" style="color: var(--white); background: rgba(255,255,255,0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" style="color: var(--white); background: rgba(255,255,255,0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: var(--white); background: rgba(255,255,255,0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: var(--white); background: rgba(255,255,255,0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.3s;"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links Column -->
                <div>
                    <h4 style="color: var(--white); margin-bottom: 24px; font-weight: 600;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/index.php" style="color: #BDBDBD; transition: color 0.3s;">Home</a></li>
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/search.php" style="color: #BDBDBD; transition: color 0.3s;">Find Care</a></li>
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/index.php#how-it-works" style="color: #BDBDBD; transition: color 0.3s;">How It Works</a></li>
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/index.php#features" style="color: #BDBDBD; transition: color 0.3s;">Features</a></li>
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/index.php#pricing" style="color: #BDBDBD; transition: color 0.3s;">Pricing</a></li>
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/about.php" style="color: #BDBDBD; transition: color 0.3s;">About Us</a></li>
                    </ul>
                </div>
                
                <!-- For Providers Column -->
                <div>
                    <h4 style="color: var(--white); margin-bottom: 24px; font-weight: 600;">For Providers</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/provider-register.php" style="color: #BDBDBD; transition: color 0.3s;">Join as a Provider</a></li>
                        <li style="margin-bottom: 12px;"><a href="<?= SITE_URL ?>/login.php" style="color: #BDBDBD; transition: color 0.3s;">Provider Login</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="color: #BDBDBD; transition: color 0.3s;">Provider Resources</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="color: #BDBDBD; transition: color 0.3s;">Safety Guidelines</a></li>
                    </ul>
                </div>
                
                <!-- Contact Column -->
                <div>
                    <h4 style="color: var(--white); margin-bottom: 24px; font-weight: 600;">Contact Us</h4>
                    <ul style="list-style: none; color: #BDBDBD; font-size: 15px; padding: 0;">
                        <li style="margin-bottom: 16px; display: flex; gap: 12px; align-items: flex-start;">
                            <i class="fas fa-map-marker-alt" style="color: var(--main-pink); margin-top: 4px;"></i> 
                            <span>123 Little Care Avenue,<br>Tech City, TC 10010</span>
                        </li>
                        <li style="margin-bottom: 16px; display: flex; gap: 12px; align-items: center;">
                            <i class="fas fa-envelope" style="color: var(--main-pink);"></i> 
                            <a href="mailto:hello@littlecare.com" style="color: #BDBDBD;">hello@littlecare.com</a>
                        </li>
                        <li style="margin-bottom: 16px; display: flex; gap: 12px; align-items: center;">
                            <i class="fas fa-phone" style="color: var(--main-pink);"></i> 
                            <a href="tel:+18005551234" style="color: #BDBDBD;">+1 (800) 555-1234</a>
                        </li>
                    </ul>
                </div>
                
            </div>
            
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px; display: flex; justify-content: space-between; align-items: center; color: #9E9E9E; font-size: 13px; flex-wrap: wrap; gap: 16px;">
                <div>
                    &copy; <?= date('Y') ?> Little Steps. All rights reserved.
                </div>
                <div style="display: flex; gap: 24px;">
                    <a href="#" style="color: #9E9E9E;">Privacy Policy</a>
                    <a href="#" style="color: #9E9E9E;">Terms of Service</a>
                    <a href="#" style="color: #9E9E9E;">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="<?= SITE_URL ?>/assets/js/app.js?v=<?= time() ?>"></script>
    <?php if(isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
    
    <style>
        footer a:hover { color: var(--main-pink) !important; }
        footer .fab:hover { background: var(--main-pink) !important; color: white !important; }
    </style>
</body>
</html>
