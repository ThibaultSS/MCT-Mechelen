<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
    </head>
    <body style="margin: 0; padding: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #07103E 0%, #147ED8 100%); font-family: Arial, sans-serif;">
        <div style="text-align: center; margin-bottom: 60px;">
            <img src="{{ asset('images/Logo_Students.png') }}" alt="Logo" class="animated-logo" style="max-width: 500px; height: auto;">
        </div>
        <div style="text-align: center; display: flex; flex-direction: column; gap: 30px; align-items: center;">
            <a href="{{ route('game.comet-typing') }}" style="display: inline-block; padding: 50px 120px; font-size: 42px; font-weight: bold; color: #07103E; background: linear-gradient(135deg, #FB6E00 0%, #FCC600 100%); border-radius: 30px; text-decoration: none; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4); transition: transform 0.2s, box-shadow 0.2s; border: 4px solid rgba(255, 255, 255, 0.3);">
                Eerste Challenge
            </a>
            <a href="{{ route('game.ai-or-not') }}" style="display: inline-block; padding: 50px 120px; font-size: 42px; font-weight: bold; color: white; background: linear-gradient(135deg, #147ED8 0%, #07103E 100%); border-radius: 30px; text-decoration: none; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4); transition: transform 0.2s, box-shadow 0.2s; border: 4px solid rgba(255, 255, 255, 0.3);">
                AI OR NOT
            </a>
        </div>
        <style>
            .animated-logo {
                animation: float 3s ease-in-out infinite, glow 2s ease-in-out infinite alternate;
                filter: drop-shadow(0 0 20px rgba(251, 110, 0, 0.5));
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
                    filter: drop-shadow(0 0 20px rgba(251, 110, 0, 0.5));
                }
                100% {
                    filter: drop-shadow(0 0 30px rgba(252, 198, 0, 0.8));
                }
            }

            a:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            }
            a:active {
                transform: translateY(-2px);
            }

            /* Responsive */
            @media (max-width: 768px) {
                .animated-logo {
                    max-width: 300px !important;
                }
                a {
                    padding: 35px 70px !important;
                    font-size: 28px !important;
                    border-radius: 25px !important;
                    width: 90%;
                }
            }
        </style>
    </body>
</html>
