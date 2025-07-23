<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Munimail - Beautiful Email Delivery. No Strings Attached.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#01A48E',
                        background: '#F9FAFB',
                        textColor: '#1E293B',
                        emerald: '#10B981',
                        rose: '#F43F5E'
                    },
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                        'playfair': ['Playfair Display', 'serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter text-textColor bg-background">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="text-2xl font-playfair font-bold text-primary">Munimail</div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-primary transition-colors">Features</a>
                    <a href="#docs" class="text-gray-600 hover:text-primary transition-colors">Docs</a>
                    <a href="#faq" class="text-gray-600 hover:text-primary transition-colors">FAQ</a>
                    <a href="https://github.com" class="text-gray-600 hover:text-primary transition-colors">GitHub</a>
                    <button class="bg-emerald text-white px-4 py-2 rounded-lg hover:bg-emerald/90 transition-colors">
                        Start Free
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-white via-background to-primary/5 pt-20 pb-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-playfair font-bold text-textColor mb-6 leading-tight">
                    Beautiful Email Delivery.<br>
                    <span class="text-primary">No Strings Attached.</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-600 mb-12 max-w-4xl mx-auto leading-relaxed">
                    Munimail is a zero-cost, privacy-respecting SMTP server built for developers who care about elegance, speed, and simplicity.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <button class="bg-emerald text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-emerald/90 transform hover:scale-105 transition-all duration-200 shadow-lg">
                        Start Sending for Free
                    </button>
                    <button class="bg-rose text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-rose/90 transform hover:scale-105 transition-all duration-200 shadow-lg flex items-center gap-2">
                        <span>♥</span> Buy Us a Coffee
                    </button>
                </div>
            </div>
        </div>
        <!-- Decorative Elements -->
        <div class="absolute top-20 left-10 w-32 h-32 bg-primary/10 rounded-full blur-xl"></div>
        <div class="absolute bottom-20 right-10 w-40 h-40 bg-emerald/10 rounded-full blur-xl"></div>
    </section>

    <!-- Feature Cards -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-playfair font-bold text-textColor mb-4">Why Choose Munimail?</h2>
                <p class="text-xl text-gray-600">Built by developers, for developers</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow border border-gray-100">
                    <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-playfair font-semibold text-textColor mb-4 text-center">Developer First</h3>
                    <p class="text-gray-600 text-center leading-relaxed">
                        Clean APIs, detailed documentation, and real-time debugging tools designed for your workflow.
                    </p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow border border-gray-100">
                    <div class="w-16 h-16 bg-emerald/10 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-playfair font-semibold text-textColor mb-4 text-center">100% Free, Truly</h3>
                    <p class="text-gray-600 text-center leading-relaxed">
                        No hidden costs, no usage limits, no premium tiers. Just reliable email delivery, always free.
                    </p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow border border-gray-100">
                    <div class="w-16 h-16 bg-rose/10 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-rose" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-playfair font-semibold text-textColor mb-4 text-center">Elegant Debugging</h3>
                    <p class="text-gray-600 text-center leading-relaxed">
                        Beautiful dashboard with real-time logs, delivery status, and comprehensive debugging tools.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-playfair font-bold text-textColor mb-4">How It Works</h2>
                <p class="text-xl text-gray-600">Get started in minutes</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mb-6 mx-auto">
                        <span class="text-white text-2xl font-bold">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-textColor mb-3">Add Domain</h3>
                    <p class="text-gray-600">Register your domain and verify DNS records with our simple setup guide.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-emerald rounded-full flex items-center justify-center mb-6 mx-auto">
                        <span class="text-white text-2xl font-bold">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-textColor mb-3">Copy Credentials</h3>
                    <p class="text-gray-600">Get your SMTP credentials and API keys from the dashboard.</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-rose rounded-full flex items-center justify-center mb-6 mx-auto">
                        <span class="text-white text-2xl font-bold">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-textColor mb-3">Send Email</h3>
                    <p class="text-gray-600">Start sending beautiful emails through SMTP or our REST API.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-playfair font-bold text-textColor mb-4">Beautiful Dashboard</h2>
                <p class="text-xl text-gray-600">Monitor your emails with real-time logs and elegant interface</p>
            </div>
            <div class="bg-gray-100 rounded-2xl p-8 shadow-xl">
                <div class="bg-white rounded-xl overflow-hidden shadow-lg">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            <span class="ml-4 text-gray-600 text-sm">dashboard.munimail.com</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg border-l-4 border-green-400">
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                    <span class="text-sm font-medium">welcome@company.com → john@example.com</span>
                                </div>
                                <span class="text-sm text-green-600">Delivered</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                                    <span class="text-sm font-medium">newsletter@company.com → subscribers</span>
                                </div>
                                <span class="text-sm text-blue-600">Sending...</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                    <span class="text-sm font-medium">support@company.com → help@example.com</span>
                                </div>
                                <span class="text-sm text-yellow-600">Queued</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Code Section -->
    <section class="py-20 bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-playfair font-bold text-textColor mb-4">Simple Integration</h2>
                <p class="text-xl text-gray-600">Choose SMTP or REST API</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-textColor">SMTP Configuration</h3>
                    </div>
                    <div class="p-6">
                        <pre class="text-sm text-gray-800 overflow-x-auto"><code>MAIL_MAILER=smtp
MAIL_HOST=smtp.munimail.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"</code></pre>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-textColor">REST API</h3>
                    </div>
                    <div class="p-6">
                        <pre class="text-sm text-gray-800 overflow-x-auto"><code>curl -X POST https://api.munimail.com/send \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "user@example.com",
    "subject": "Welcome!",
    "html": "&lt;h1&gt;Hello World&lt;/h1&gt;"
  }'</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Donation Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-playfair font-bold text-textColor mb-6">Built with intention. Supported by kindness.</h2>
            <p class="text-xl text-gray-600 mb-12 leading-relaxed">
                Munimail is a labor of love, built and maintained by developers who believe in free, quality tools. 
                Your support helps us keep the servers running and the coffee flowing.
            </p>
            <button class="bg-rose text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-rose/90 transform hover:scale-105 transition-all duration-200 shadow-lg flex items-center gap-3 mx-auto">
                <span>♥</span> Buy us a coffee
            </button>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-playfair font-bold text-textColor mb-4">Loved by Developers</h2>
                <p class="text-xl text-gray-600">See what our community says</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-semibold">
                            S
                        </div>
                        <div class="ml-4">
                            <div class="font-semibold text-textColor">Sarah Chen</div>
                            <div class="text-gray-600 text-sm">Lead Developer @ TechCorp</div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic leading-relaxed">
                        "Finally, an SMTP service that just works. The debugging dashboard saved me hours of troubleshooting."
                    </p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-emerald rounded-full flex items-center justify-center text-white font-semibold">
                            M
                        </div>
                        <div class="ml-4">
                            <div class="font-semibold text-textColor">Marcus Johnson</div>
                            <div class="text-gray-600 text-sm">Freelance Developer</div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic leading-relaxed">
                        "Beautiful interface, reliable delivery, and completely free. This is how email services should be built."
                    </p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-rose rounded-full flex items-center justify-center text-white font-semibold">
                            A
                        </div>
                        <div class="ml-4">
                            <div class="font-semibold text-textColor">Alex Rivera</div>
                            <div class="text-gray-600 text-sm">Startup Founder</div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic leading-relaxed">
                        "Setup took 5 minutes. The real-time logs are incredible. Munimail is now our go-to email solution."
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-playfair font-bold text-textColor mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-gray-600">Everything you need to know</p>
            </div>
            <div class="space-y-4">
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button class="w-full px-6 py-4 text-left bg-gray-50 hover:bg-gray-100 transition-colors flex justify-between items-center" onclick="toggleFaq(0)">
                        <span class="font-semibold text-textColor">Is Munimail really completely free?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" id="faq-icon-0">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="px-6 py-4 bg-white hidden" id="faq-content-0">
                        <p class="text-gray-600">Yes, absolutely. There are no hidden costs, usage limits, or premium tiers. We believe email delivery should be accessible to everyone.</p>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button class="w-full px-6 py-4 text-left bg-gray-50 hover:bg-gray-100 transition-colors flex justify-between items-center" onclick="toggleFaq(1)">
                        <span class="font-semibold text-textColor">What are the sending limits?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" id="faq-icon-1">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="px-6 py-4 bg-white hidden" id="faq-content-1">
                        <p class="text-gray-600">We implement reasonable rate limits to prevent abuse, but they're generous enough for most applications. Contact us if you need higher limits.</p>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button class="w-full px-6 py-4 text-left bg-gray-50 hover:bg-gray-100 transition-colors flex justify-between items-center" onclick="toggleFaq(2)">
                        <span class="font-semibold text-textColor">How do you ensure deliverability?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" id="faq-icon-2">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="px-6 py-4 bg-white hidden" id="faq-content-2">
                        <p class="text-gray-600">We maintain excellent IP reputation, implement all authentication protocols (SPF, DKIM, DMARC), and monitor delivery rates closely.</p>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button class="w-full px-6 py-4 text-left bg-gray-50 hover:bg-gray-100 transition-colors flex justify-between items-center" onclick="toggleFaq(3)">
                        <span class="font-semibold text-textColor">Can I use my own domain?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" id="faq-icon-3">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="px-6 py-4 bg-white hidden" id="faq-content-3">
                        <p class="text-gray-600">Absolutely! You can send emails from any domain you own. Just verify it through our simple DNS setup process.</p>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button class="w-full px-6 py-4 text-left bg-gray-50 hover:bg-gray-100 transition-colors flex justify-between items-center" onclick="toggleFaq(4)">
                        <span class="font-semibold text-textColor">What about support?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" id="faq-icon-4">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="px-6 py-4 bg-white hidden" id="faq-content-4">
                        <p class="text-gray-600">We provide community support through GitHub issues and documentation. Priority support is available for those who support us with donations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-textColor text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="text-3xl font-playfair font-bold mb-4">Munimail</div>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        Built with Laravel, ReactPHP, and care. Inspired by Muni.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Resources</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-white transition-colors">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API Reference</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">GitHub</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Status Page</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Company</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition-colors flex items-center gap-2">
                            <span>♥</span> Donate
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Munimail. Built with Laravel, ReactPHP, and care. Inspired by Muni.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleFaq(index) {
            const content = document.getElementById(`faq-content-${index}`);
            const icon = document.getElementById(`faq-icon-${index}`);
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>