<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pesan kontak baru</title>
</head>
<body style="margin:0;padding:24px;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e4ded2;border-radius:8px;overflow:hidden;">
        <div style="background:#0b2836;color:#ffffff;padding:20px 24px;">
            <p style="margin:0;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#dda43a;">Portal SUPD I</p>
            <h1 style="margin:6px 0 0;font-size:20px;">Pesan kontak baru</h1>
        </div>
        <div style="padding:24px;">
            <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;font-size:14px;line-height:1.6;">
                <tr>
                    <td style="width:120px;color:#66727c;padding:4px 0;">Nama</td>
                    <td style="padding:4px 0;font-weight:bold;">{{ $contactMessage->name }}</td>
                </tr>
                <tr>
                    <td style="color:#66727c;padding:4px 0;">Email</td>
                    <td style="padding:4px 0;">
                        <a href="mailto:{{ $contactMessage->email }}" style="color:#0b4f63;">{{ $contactMessage->email }}</a>
                    </td>
                </tr>
                @if ($contactMessage->phone)
                    <tr>
                        <td style="color:#66727c;padding:4px 0;">Telepon</td>
                        <td style="padding:4px 0;">{{ $contactMessage->phone }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="color:#66727c;padding:4px 0;">Subjek</td>
                    <td style="padding:4px 0;font-weight:bold;">{{ $contactMessage->subject }}</td>
                </tr>
                <tr>
                    <td style="color:#66727c;padding:4px 0;">Diterima</td>
                    <td style="padding:4px 0;">{{ $contactMessage->created_at?->translatedFormat('d F Y, H:i') }} WIB</td>
                </tr>
            </table>

            <div style="margin-top:20px;border-top:1px solid #e4ded2;padding-top:16px;">
                <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.1em;text-transform:uppercase;color:#66727c;">Isi pesan</p>
                <div style="font-size:14px;line-height:1.7;white-space:pre-line;">{{ $contactMessage->message }}</div>
            </div>

            <p style="margin:24px 0 0;font-size:12px;color:#66727c;">
                Balas email ini untuk menjawab langsung ke pengirim. Perbarui status pesan melalui panel admin.
            </p>
        </div>
    </div>
</body>
</html>
