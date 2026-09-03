<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Landing Page - Little Steps
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* Section padding */
    .section-padding { padding: 100px 0; }
    
    /* Typography tweaks for homepage */
    .hero-h1 { font-size: 3.5rem; line-height: 1.1; color: var(--dark-pink); font-weight: 700; margin-bottom: 24px; letter-spacing: -1px; }
    .hero-sub { font-size: 1.125rem; color: #616161; line-height: 1.6; margin-bottom: 32px; max-width: 90%; }
    .section-title { font-size: 2.5rem; color: var(--near-black); text-align: center; margin-bottom: 12px; font-weight: 700; letter-spacing: -0.5px; }
    .section-sub { font-size: 1.125rem; color: #616161; text-align: center; margin-bottom: 64px; }
    
    /* Custom Components */
    .feature-card { background: var(--white); border-radius: 24px; padding: 40px 32px; box-shadow: 0 10px 40px rgba(233, 30, 99, 0.08); text-align: center; transition: transform 0.3s; }
    .feature-card:hover { transform: translateY(-8px); }
    .feature-icon { width: 64px; height: 64px; background: var(--light-pink); color: var(--main-pink); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 24px; }
    
    .step-circle { width: 80px; height: 80px; background: linear-gradient(135deg, var(--main-pink), var(--accent-pink)); color: var(--white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; margin: 0 auto 24px; box-shadow: 0 8px 24px rgba(233, 30, 99, 0.3); }
    
    .testimonial-card { background: var(--white); border-radius: 24px; padding: 32px; box-shadow: 0 10px 40px rgba(233, 30, 99, 0.05); }
    
    .pricing-card { background: var(--white); border-radius: 24px; padding: 40px; box-shadow: 0 10px 40px rgba(233, 30, 99, 0.05); text-align: center; position: relative; }
    .pricing-card.popular { border: 2px solid var(--main-pink); transform: scale(1.05); z-index: 2; box-shadow: 0 20px 50px rgba(233, 30, 99, 0.15); }
    .popular-badge { position: absolute; top: -16px; left: 50%; transform: translateX(-50%); background: var(--main-pink); color: var(--white); padding: 6px 20px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    
    @media (max-width: 991px) {
        .hero-grid { grid-template-columns: 1fr !important; gap: 40px; text-align: center; }
        .hero-h1 { font-size: 2.5rem; }
        .hero-sub { margin: 0 auto 32px; }
        .hero-buttons { justify-content: center; }
        .pricing-card.popular { transform: scale(1); }
    }
</style>

<!-- 1. Hero Section -->
<section style="background: linear-gradient(180deg, var(--light-pink) 0%, var(--white) 100%); padding: 120px 0 80px; overflow: hidden;">
    <div class="container hero-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
        
        <div style="z-index: 2;">
            <div style="display: inline-block; background: var(--white); border-radius: 50px; padding: 8px 20px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(233, 30, 99, 0.08); border: 1px solid var(--light-pink);">
                <span style="font-size: 14px; font-weight: 600; color: var(--dark-gray);">Trusted by 10,000+ parents</span>
            </div>
            
            <h1 class="hero-h1">Safe, Loving Childcare for Your Little Ones</h1>
            
            <p class="hero-sub">
                Find vetted caregivers and daycare centers near you, available 24/7. Book in minutes and enjoy peace of mind.
            </p>
            
            <div class="hero-buttons" style="display: flex; gap: 16px; margin-bottom: 40px; flex-wrap: wrap;">
                <a href="search.php" class="btn btn-primary btn-lg" style="padding: 16px 40px;">Find Care Now</a>
                <a href="#how-it-works" class="btn btn-outline btn-lg" style="padding: 16px 40px; border-color: var(--main-pink); color: var(--main-pink); background: transparent;">How It Works</a>
            </div>
            
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="display: flex;">
                    <img src="https://ui-avatars.com/api/?name=P1&background=F8BBD9&color=AD1457" alt="Parent" style="width: 48px; height: 48px; border-radius: 50%; border: 3px solid white; margin-right: -15px; z-index: 3;">
                    <img src="https://ui-avatars.com/api/?name=P2&background=FCE4EC&color=E91E63" alt="Parent" style="width: 48px; height: 48px; border-radius: 50%; border: 3px solid white; margin-right: -15px; z-index: 2;">
                    <img src="https://ui-avatars.com/api/?name=P3&background=FFF0F5&color=F50057" alt="Parent" style="width: 48px; height: 48px; border-radius: 50%; border: 3px solid white; z-index: 1;">
                </div>
                <div>
                    <div style="color: var(--main-pink); font-size: 16px; margin-bottom: 2px;">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <span style="font-size: 13px; font-weight: 600; color: var(--dark-gray);">4.9/5 from 2,300 reviews</span>
                </div>
            </div>
        </div>
        
        <div style="position: relative; z-index: 1;">
            <div style="width: 100%; aspect-ratio: 4/5; border-radius: 40px 120px 40px 40px; overflow: hidden; border: 8px solid var(--white); box-shadow: 0 20px 60px rgba(233, 30, 99, 0.15); position: relative;">
                <img src="<?= SITE_URL ?>/assets/images/hero.jpg" alt="Caregiver playing with toddler" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <!-- Decorative Elements -->
            <div style="position: absolute; top: 10%; right: -20px; width: 60px; height: 60px; background: var(--white); border-radius: 50%; box-shadow: 0 10px 20px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--main-pink); animation: bounce 3s infinite;">
                <i class="fas fa-heart"></i>
            </div>
            <div style="position: absolute; bottom: 15%; left: -30px; background: var(--white); border-radius: 20px; padding: 12px 20px; box-shadow: 0 10px 30px rgba(233,30,99,0.15); display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: var(--success-bg); color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fas fa-shield-check"></i>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 14px; color: var(--near-black);">100% Verified</div>
                    <div style="font-size: 12px; color: var(--medium-gray);">Caregivers</div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<!-- 2. Features Section -->
<section id="features" class="section-padding" style="background: var(--white);">
    <div class="container">
        <h2 class="section-title">Why Parents Choose Little Steps</h2>
        <p class="section-sub">We combine safety, flexibility, and love.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center;">
            <div style="display: grid; grid-template-columns: 1fr; gap: 32px;">
                <!-- Feature 1 -->
                <div class="feature-card" style="padding: 24px; display: flex; align-items: flex-start; gap: 20px; text-align: left;">
                    <div class="feature-icon" style="margin: 0; min-width: 64px;"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 8px;">Safety First</h3>
                        <p style="color: #616161; font-size: 15px; margin: 0;">Stringent background checks and CCTV-enabled centers for absolute peace of mind.</p>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card" style="padding: 24px; display: flex; align-items: flex-start; gap: 20px; text-align: left;">
                    <div class="feature-icon" style="margin: 0; min-width: 64px;"><i class="fas fa-clock"></i></div>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 8px;">24/7 Availability</h3>
                        <p style="color: #616161; font-size: 15px; margin: 0;">Flexible timings to support shift workers and unexpected emergency drop-ins.</p>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card" style="padding: 24px; display: flex; align-items: flex-start; gap: 20px; text-align: left;">
                    <div class="feature-icon" style="margin: 0; min-width: 64px;"><i class="fas fa-heart"></i></div>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 8px;">Loving Environment</h3>
                        <p style="color: #616161; font-size: 15px; margin: 0;">Compassionate care that feels like home, focusing on your child's happiness.</p>
                    </div>
                </div>
            </div>
            <div style="border-radius: 40px; overflow: hidden; box-shadow: 0 20px 40px rgba(233, 30, 99, 0.15); height: 100%;">
                <img src="<?= SITE_URL ?>/assets/images/feature_safety.jpg" alt="Safe Playroom" style="width: 100%; height: 100%; object-fit: cover; min-height: 500px;">
            </div>
        </div>
    </div>
</section>

<!-- 3. How It Works -->
<section id="how-it-works" class="section-padding" style="background: var(--baby-pink);">
    <div class="container">
        <h2 class="section-title">Booking Care in 3 Simple Steps</h2>
        <p class="section-sub">Finding the perfect care is easier than ever.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px; text-align: center;">
            <div>
                <div class="step-circle">1</div>
                <h3 style="font-size: 1.35rem; margin-bottom: 12px;">Search & Select</h3>
                <p style="color: #616161; font-size: 15px;">Enter your location and preferences to browse a curated list of vetted centers and caregivers.</p>
            </div>
            
            <div>
                <div class="step-circle">2</div>
                <h3 style="font-size: 1.35rem; margin-bottom: 12px;">Meet & Greet</h3>
                <p style="color: #616161; font-size: 15px;">Review profiles, read verified reviews, and chat with providers to ensure a perfect match.</p>
            </div>
            
            <div>
                <div class="step-circle">3</div>
                <h3 style="font-size: 1.35rem; margin-bottom: 12px;">Book Care</h3>
                <p style="color: #616161; font-size: 15px;">Securely book your slot online. Relax knowing your child is in the best hands.</p>
            </div>
        </div>
    </div>
</section>

<!-- 4. Testimonials -->
<section class="section-padding" style="background: var(--white);">
    <div class="container">
        <h2 class="section-title">What Parents Say</h2>
        <p class="section-sub">Don't just take our word for it.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px;">
            
            <div class="testimonial-card">
                <div style="color: var(--main-pink); font-size: 16px; margin-bottom: 16px;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p style="font-style: italic; color: var(--dark-gray); font-size: 15px; margin-bottom: 24px;">"Finding reliable night care was a nightmare until I found Little Steps. The center is amazing and the booking process is seamless."</p>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <img src="https://ui-avatars.com/api/?name=Sarah+J" style="width: 48px; height: 48px; border-radius: 50%;">
                    <div>
                        <div style="font-weight: 700; color: var(--near-black);">Sarah Jenkins</div>
                        <div style="font-size: 13px; color: var(--medium-gray);">Parent, Nurse</div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div style="color: var(--main-pink); font-size: 16px; margin-bottom: 16px;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p style="font-style: italic; color: var(--dark-gray); font-size: 15px; margin-bottom: 24px;">"The caregivers are so compassionate. I love the instant updates and the ability to book emergency drop-ins on short notice."</p>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <img src="https://ui-avatars.com/api/?name=Mark+T" style="width: 48px; height: 48px; border-radius: 50%;">
                    <div>
                        <div style="font-weight: 700; color: var(--near-black);">Mark Thompson</div>
                        <div style="font-size: 13px; color: var(--medium-gray);">Parent</div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div style="color: var(--main-pink); font-size: 16px; margin-bottom: 16px;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p style="font-style: italic; color: var(--dark-gray); font-size: 15px; margin-bottom: 24px;">"As a single mom, this platform is a lifesaver. It's incredibly user-friendly and every center we've visited has been top-notch."</p>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <img src="https://ui-avatars.com/api/?name=Elena+R" style="width: 48px; height: 48px; border-radius: 50%;">
                    <div>
                        <div style="font-weight: 700; color: var(--near-black);">Elena Rodriguez</div>
                        <div style="font-size: 13px; color: var(--medium-gray);">Parent</div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- 5. Pricing Section -->
<section id="pricing" class="section-padding" style="background: var(--baby-pink);">
    <div class="container">
        <h2 class="section-title">Simple, Transparent Pricing</h2>
        <p class="section-sub">Choose a plan that fits your childcare needs.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; align-items: center; max-width: 1000px; margin: 0 auto;">
            
            <!-- Basic -->
            <div class="pricing-card">
                <h3 style="font-size: 1.5rem; color: var(--dark-gray); margin-bottom: 16px;">Hourly Drop-in</h3>
                <div style="font-size: 3rem; font-weight: 700; color: var(--near-black); margin-bottom: 8px;">₹250<span style="font-size: 16px; color: var(--medium-gray); font-weight: 500;">/hr</span></div>
                <p style="color: var(--medium-gray); font-size: 14px; margin-bottom: 32px;">Perfect for occasional needs.</p>
                
                <ul style="list-style: none; padding: 0; text-align: left; margin-bottom: 32px;">
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--success); width: 24px;"></i> Flexible booking</li>
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--success); width: 24px;"></i> Emergency availability</li>
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--success); width: 24px;"></i> Basic activity updates</li>
                </ul>
                
                <a href="register.php" class="btn btn-outline" style="width: 100%;">Get Started</a>
            </div>
            
            <!-- Standard -->
            <div class="pricing-card popular">
                <div class="popular-badge">Most Popular</div>
                <h3 style="font-size: 1.5rem; color: var(--main-pink); margin-bottom: 16px;">Weekly Standard</h3>
                <div style="font-size: 3rem; font-weight: 700; color: var(--near-black); margin-bottom: 8px;">₹4500<span style="font-size: 16px; color: var(--medium-gray); font-weight: 500;">/wk</span></div>
                <p style="color: var(--medium-gray); font-size: 14px; margin-bottom: 32px;">Ideal for regular working hours.</p>
                
                <ul style="list-style: none; padding: 0; text-align: left; margin-bottom: 32px;">
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--main-pink); width: 24px;"></i> Guaranteed slot</li>
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--main-pink); width: 24px;"></i> Priority support</li>
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--main-pink); width: 24px;"></i> Full daily reports</li>
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--main-pink); width: 24px;"></i> Includes meals</li>
                </ul>
                
                <a href="register.php" class="btn btn-primary" style="width: 100%;">Subscribe Now</a>
            </div>
            
            <!-- Premium -->
            <div class="pricing-card">
                <h3 style="font-size: 1.5rem; color: var(--dark-gray); margin-bottom: 16px;">Monthly Premium</h3>
                <div style="font-size: 3rem; font-weight: 700; color: var(--near-black); margin-bottom: 8px;">₹15000<span style="font-size: 16px; color: var(--medium-gray); font-weight: 500;">/mo</span></div>
                <p style="color: var(--medium-gray); font-size: 14px; margin-bottom: 32px;">For complete 24/7 flexibility.</p>
                
                <ul style="list-style: none; padding: 0; text-align: left; margin-bottom: 32px;">
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--success); width: 24px;"></i> Unrestricted 24x7 access</li>
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--success); width: 24px;"></i> 1-on-1 tutoring options</li>
                    <li style="margin-bottom: 12px; font-size: 15px; color: var(--dark-gray);"><i class="fas fa-check" style="color: var(--success); width: 24px;"></i> Premium medical coverage</li>
                </ul>
                
                <a href="register.php" class="btn btn-outline" style="width: 100%;">Get Started</a>
            </div>
            
        </div>
    </div>
</section>

<!-- 6. For Providers Banner -->
<section style="background: var(--dark-pink); padding: 80px 0;">
    <div class="container" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 32px;">
        <div>
            <h2 style="color: var(--white); font-size: 2.5rem; margin-bottom: 12px;">Are you a childcare provider?</h2>
            <p style="color: rgba(255,255,255,0.9); font-size: 1.125rem; margin: 0;">Join our network and grow your business with Little Steps's platform.</p>
        </div>
        <a href="provider-register.php" class="btn" style="background: var(--white); color: var(--dark-pink); padding: 16px 40px; font-size: 16px;">
            Become a Provider
        </a>
    </div>
</section>

<style>
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
