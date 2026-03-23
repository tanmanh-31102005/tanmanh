<?php
require_once __DIR__ . '/../models/Settings.php';
?>
</main>
    <style>
        footer {
            background-color: var(--bg-secondary);
            padding: 2rem 0;
            margin-top: 3rem;
            color: var(--text-secondary);
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-heading {
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .footer-list {
            list-style: none;
        }
        
        .footer-link {
            color: var(--text-secondary);
            text-decoration: none;
        }
        
        .contact-item {
            margin-bottom: 0.5rem;
        }
        
        .contact-icon {
            display: inline;
            margin-right: 0.5rem;
        }
        
        .footer-divider {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            padding: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3 class="footer-heading">KOL/KOC Booking Platform</h3>
                    <p>Nền tảng kết nối Thương hiệu với KOL/KOC hàng đầu Việt Nam</p>
                </div>
                <div>
                    <h4 class="footer-heading">Liên kết</h4>
                    <ul class="footer-list">
                        <li><a href="../view/about.php" class="footer-link">Về chúng tôi</a></li>
                        <li><a href="../view/terms.php" class="footer-link">Điều khoản sử dụng</a></li>
                        <li><a href="../view/privacy.php" class="footer-link">Chính sách bảo mật</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-heading">Liên hệ</h4>
                    <ul class="footer-list">
                        <?php if ($email = Settings::get('contact_email')): ?>
                            <li class="contact-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="contact-icon">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <?= htmlspecialchars($email) ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($phone = Settings::get('contact_phone')): ?>
                            <li class="contact-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="contact-icon">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <?= htmlspecialchars($phone) ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($address = Settings::get('contact_address')): ?>
                            <li class="contact-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="contact-icon">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span><?= htmlspecialchars_decode($address) ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-divider">
                <p>© <?= date('Y') ?> KOL/KOC Booking Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.className = 'mobile-menu-btn';
            mobileMenuBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            `;
            
            const nav = document.querySelector('nav');
            const menu = document.querySelector('nav ul');
            
            nav.insertBefore(mobileMenuBtn, menu);
            
            mobileMenuBtn.addEventListener('click', function() {
                menu.classList.toggle('active');
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!nav.contains(e.target)) {
                    menu.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>