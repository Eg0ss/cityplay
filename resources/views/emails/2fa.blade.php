<!DOCTYPE html>
<html>
<head>
    <title>Votre code de double authentification</title>
</head>
<body style="font-family: sans-serif; background-color: #171235; color: #ffffff; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #10101c; padding: 40px; border-radius: 20px; border: 1px solid #2a245c;">
        <h1 style="color: #87d74e; text-transform: uppercase; font-style: italic;">Cityplay</h1>
        <p style="font-size: 16px; line-height: 1.5;">Bonjour,</p>
        <p style="font-size: 16px; line-height: 1.5;">Voici votre code de double authentification pour accéder à votre compte :</p>
        <div style="background-color: #171235; padding: 20px; border-radius: 10px; text-align: center; margin: 30px 0; border: 1px solid #87d74e;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #87d74e;">{{ $code }}</span>
        </div>
        <p style="font-size: 14px; color: #a0aec0;">Si vous n'avez pas demandé ce code, veuillez ignorer cet e-mail.</p>
        <hr style="border: 0; border-top: 1px solid #2a245c; margin: 30px 0;">
        <p style="font-size: 12px; color: #718096; text-align: center;">&copy; {{ date('Y') }} Cityplay. Tous droits réservés.</p>
    </div>
</body>
</html>
