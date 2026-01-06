<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="margin: 0; padding: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #07103E 0%, #147ED8 100%); font-family: Arial, sans-serif;">
    <div style="width: 100%; max-width: 500px; padding: 2rem; display: flex; flex-direction: column; align-items: center;">
        <div style="text-align: center; margin-bottom: 3rem; width: 100%; display: flex; justify-content: center;">
            <img src="{{ asset('images/Logo_Students.png') }}" alt="Logo" class="animated-logo" style="max-width: 400px; height: auto; display: block; margin: 0 auto;">
        </div>
        
        <div style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 3rem; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); width: 100%; box-sizing: border-box;">
            <h1 style="color: #07103E; font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem; text-align: center;">Welkom!</h1>
            <p style="color: #706f6c; text-align: center; margin-bottom: 2rem;">Vul je gegevens in om te beginnen</p>
            
            <form method="POST" action="/loginRun" id="loginForm" style="display: flex; flex-direction: column; gap: 1.5rem;">
                @csrf
                
                <div>
                    <label for="username" style="display: block; color: #07103E; font-weight: 600; margin-bottom: 0.5rem;">Naam</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        class="form-input"
                        style="width: 100%; padding: 1rem; font-size: 1rem; border: 2px solid #e3e3e0; border-radius: 10px; background: white; color: #07103E; box-sizing: border-box; transition: all 0.2s;"
                        placeholder="Voer je naam in"
                    >
                </div>
                
                <div>
                    <label for="email" style="display: block; color: #07103E; font-weight: 600; margin-bottom: 0.5rem;">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="form-input"
                        style="width: 100%; padding: 1rem; font-size: 1rem; border: 2px solid #e3e3e0; border-radius: 10px; background: white; color: #07103E; box-sizing: border-box; transition: all 0.2s;"
                        placeholder="voer.jouw@email.in"
                    >
                </div>
                
                <button 
                    type="submit" 
                    class="btn-submit"
                    style="padding: 1rem 2rem; font-size: 1.125rem; font-weight: bold; color: #07103E; background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%); border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); margin-top: 0.5rem;"
                >
                    Starten
                </button>
            </form>
        </div>
    </div>

    <style>
        .animated-logo {
            animation: float 3s ease-in-out infinite, glow 2s ease-in-out infinite alternate;
            filter: drop-shadow(0 0 20px rgba(251, 110, 0, 0.5));
        }

        .form-input:focus {
            border-color: #FCC600 !important;
            box-shadow: 0 0 0 3px rgba(252, 198, 0, 0.2) !important;
            outline: none;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3) !important;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(2deg);
            }
        }

        @keyframes glow {
            0% {
                filter: drop-shadow(0 0 20px rgba(252, 198, 0, 0.5));
            }
            100% {
                filter: drop-shadow(0 0 30px rgba(251, 110, 0, 0.8));
            }
        }

        @media (max-width: 768px) {
            .animated-logo {
                max-width: 280px !important;
            }
        }
    </style>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            button.textContent = 'Bezig...';
            button.disabled = true;
        });
    </script>
</body>
</html>
