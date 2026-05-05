<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Plan de Prévention SALTI</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #000; background: #fff; margin: 0; padding: 0; }
    .wrap { max-width: 600px; margin: 0 auto; padding: 24px; }
    .header { background: #000; color: #fff; padding: 16px 24px; }
    .badge { background: #FFC000; color: #000; font-weight: bold; padding: 6px 12px; border-radius: 4px; display: inline-block; }
    .content { padding: 24px; background: #fff; border: 1px solid #e5e5e5; }
    .btn { display: inline-block; background: #FFC000; color: #000 !important; font-weight: bold; padding: 14px 28px; text-decoration: none; border-radius: 6px; }
    .info { background: #fafafa; border-left: 3px solid #FFC000; padding: 12px 16px; margin: 16px 0; font-size: 13px; }
    .footer { font-size: 12px; color: #777; text-align: center; padding: 16px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <span class="badge">SALTI</span>
        <span style="margin-left: 8px;">Plan de Prévention 2026</span>
    </div>
    <div class="content">
        <h2 style="margin-top: 0;">Plan de Prévention à compléter</h2>
        <p>Bonjour,</p>
        <p>
            Vous intervenez prochainement sur un site SALTI pour l'opération suivante :
        </p>

        <div class="info">
            <strong>{{ $pdp->data['operation']['designation'] ?? 'Intervention' }}</strong><br>
            📍 Lieu : {{ $pdp->data['operation']['lieu'] ?? '—' }}<br>
            📅 Date début : {{ $pdp->data['operation']['date_debut'] ?? '—' }}<br>
            🏢 Agence : {{ $agency->name }}
        </div>

        <p>
            Conformément au Code du travail, un Plan de Prévention doit être établi
            <strong>conjointement</strong> entre nos deux entreprises avant le début de l'intervention.
        </p>

        <p>
            Merci de cliquer sur le lien ci-dessous pour accéder à votre espace prestataire et compléter
            les informations de votre entreprise (raison sociale, habilitations de vos salariés, etc.).
        </p>

        <p style="text-align: center; margin: 32px 0;">
            <a href="{{ $magicUrl }}" class="btn">📋 Compléter le Plan de Prévention</a>
        </p>

        <p style="font-size: 13px; color: #555;">
            Ce lien sera valable <strong>7 jours</strong>. Au-delà, contactez-nous pour qu'on vous en
            génère un nouveau. Vos saisies sont enregistrées automatiquement, vous pouvez fermer
            l'onglet et y revenir plus tard.
        </p>
    </div>
    <div class="footer">
        Cet email a été envoyé automatiquement par {{ $agency->name }} ({{ $agency->email }}).<br>
        Pour toute question, contactez directement votre interlocuteur SALTI.
    </div>
</div>
</body>
</html>
