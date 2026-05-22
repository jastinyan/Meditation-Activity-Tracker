<?php
session_start();
require 'db_connection.php';
require 'ip_helper.php'; // Add this line

// If user was logged in, log the logout
if (isset($_SESSION['id_no']) && isset($_SESSION['username'])) {
    $id_no = $_SESSION['id_no'];
    $username = $_SESSION['username'];
    
    // Get enhanced browser and IP info
    $ip_address = getRealIP();
    $browserInfo = getBrowserInfo();
    $user_agent = $browserInfo['full'];

    $stmt = $conn->prepare(
        "INSERT INTO system_logs (id_no, username, action, browser, ip_address) VALUES (?, ?, 'LOGOUT', ?, ?)"
    );
    $stmt->bind_param("ssss", $id_no, $username, $user_agent, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Destroy session regardless
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/homepage.css">
    <link rel="shortcut icon" href="../img/med-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <title>MAT - Meditation Activity Tracker</title>
</head>
<body>
    <div class="navbar">
        <p class="system-title">Meditation Activity Tracker</p>
    </div>

    <div class="hero-section">
        <div class="hero-content container">
            <div class="hourglass-wrapper">
                <div class="hourglass-container">
                    <!-- Hourglass Frame -->
                    <div class="hourglass-frame"></div>
                    
                    <!-- Main Hourglass -->
                    <div class="hourglass">
                        <!-- Top bulb -->
                        <div class="glass top-bulb">
                            <div class="bulb-reflection"></div>
                            <div class="sand top-sand"></div>
                            <div class="sand-fall-container">
                                <div class="sand-fall"></div>
                            </div>
                        </div>
                        
                        <!-- Neck/Middle section -->
                        <div class="hourglass-neck"></div>
                        
                        <!-- Bottom bulb -->
                        <div class="glass bottom-bulb">
                            <div class="bulb-reflection"></div>
                            <div class="sand bottom-sand"></div>
                        </div>
                        
                        <!-- Wooden ends -->
                        <div class="hourglass-top"></div>
                        <div class="hourglass-bottom"></div>
                        
                        <!-- Side supports -->
                        <div class="support left-support"></div>
                        <div class="support right-support"></div>
                    </div>
                    
                    <!-- Sand particles (floating) -->
                    <div class="sand-particles"></div>
                </div>
            </div>
            
            <div class="hero-text">
                <h1 class="animated-title">
                    <span class="title-word">WELCOME</span>
                    <span class="title-word">TO MEDITRACK</span>

                </h1>
                <p class="hero-description">
                    Where we track your meditation activities and help you achieve your desired peacefulness.
                    <span class="typing-cursor">|</span>
                </p>
                <div class="hero-buttons">
                    <a href="../phpdb/register.php" class="cta-button primary">Register</a>
                    <a href="../phpdb/login.php" class="cta-button secondary">Login</a>
                </div>
            </div>
        </div>
        
        <!-- Decorative elements -->
        <div class="floating-particle particle1"></div>
        <div class="floating-particle particle2"></div>
        <div class="floating-particle particle3"></div>
        <div class="floating-particle particle4"></div>
    </div>

    <!-- Features Section -->
    <div class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose MAT?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🧘</div>
                    <h3>Track Sessions</h3>
                    <p>Log your meditation sessions with duration, mood, and location</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Visual Progress</h3>
                    <p>Beautiful charts and insights to monitor your journey</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3>Achievements</h3>
                    <p>Earn badges and unlock milestones as you progress</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3>Calendar View</h3>
                    <p>See your meditation history at a glance</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <p class="all">&copy; 2026 Meditation Activity Tracker. All rights reserved.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hourglass = document.querySelector('.hourglass');
            const topSand = document.querySelector('.top-sand');
            const bottomSand = document.querySelector('.bottom-sand');
            const sandFall = document.querySelector('.sand-fall');
            const sandParticles = document.querySelector('.sand-particles');
            
            let flipped = false;
            const fillDuration = 4000; // 4 seconds for a more relaxed pace
            
            // Create floating sand particles
            function createParticles() {
                for (let i = 0; i < 20; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDelay = Math.random() * 5 + 's';
                    particle.style.animationDuration = 3 + Math.random() * 4 + 's';
                    sandParticles.appendChild(particle);
                }
            }
            
            // Update sand fall position and intensity
            function updateSandFall(progress) {
                if (!sandFall) return;
                
                // Sand fall gets thinner as top empties
                const width = 4 + (progress * 4); // 4-8px width
                const opacity = 0.3 + (progress * 0.5); // 0.3-0.8 opacity
                
                sandFall.style.width = width + 'px';
                sandFall.style.opacity = opacity;
                sandFall.style.animation = `sand-fall ${fillDuration/1000}s linear infinite`;
            }
            
            // Main animation cycle
            function runHourglassCycle() {
                // Reset any existing animations
                topSand.style.animation = 'none';
                bottomSand.style.animation = 'none';
                sandFall.style.animation = 'none';
                
                // Force reflow
                void topSand.offsetWidth;
                
                // Determine direction based on flip state
                if (!flipped) {
                    // Sand flowing from top to bottom
                    topSand.style.animation = `empty-sand ${fillDuration}ms ease-in forwards`;
                    bottomSand.style.animation = `fill-sand ${fillDuration}ms ease-out forwards`;
                    
                    // Show sand fall
                    sandFall.style.display = 'block';
                    updateSandFall(1);
                    
                    // Update sand fall intensity over time
                    let startTime = Date.now();
                    function updateIntensity() {
                        const elapsed = Date.now() - startTime;
                        const progress = Math.min(elapsed / fillDuration, 1);
                        
                        if (progress < 1) {
                            updateSandFall(1 - progress); // Decreasing as top empties
                            requestAnimationFrame(updateIntensity);
                        }
                    }
                    requestAnimationFrame(updateIntensity);
                } else {
                    // Sand flowing from bottom to top (hourglass flipped)
                    bottomSand.style.animation = `empty-sand ${fillDuration}ms ease-in forwards`;
                    topSand.style.animation = `fill-sand ${fillDuration}ms ease-out forwards`;
                    
                    // Hide sand fall when flipping
                    sandFall.style.display = 'none';
                }
                
                // Schedule the flip
                setTimeout(() => {
                    // Add flip animation class
                    hourglass.classList.add('flipping');
                    
                    // Perform the flip
                    setTimeout(() => {
                        hourglass.style.transform = flipped ? 'rotateX(0deg)' : 'rotateX(180deg)';
                        flipped = !flipped;
                        
                        // Remove flip animation class after flip completes
                        setTimeout(() => {
                            hourglass.classList.remove('flipping');
                            
                            // Start next cycle
                            setTimeout(runHourglassCycle, 500);
                        }, 600);
                    }, 50);
                    
                }, fillDuration + 200);
            }
            
            // Initialize
            createParticles();
            
            // Set initial sand levels
            topSand.style.height = '90%';
            bottomSand.style.height = '10%';
            
            // Start the animation after a short delay
            setTimeout(runHourglassCycle, 1000);
            
            // Add mouse interaction effect
            hourglass.addEventListener('mouseenter', () => {
                hourglass.style.filter = 'drop-shadow(0 0 20px rgba(255,102,0,0.5))';
            });
            
            hourglass.addEventListener('mouseleave', () => {
                hourglass.style.filter = 'drop-shadow(0 0 10px rgba(255,102,0,0.3))';
            });
        });
    </script>
</body>
</html>