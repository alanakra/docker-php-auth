# SSL Certificate Troubleshooting

## Error: "Your connection is not private" / "ERR_CERT_AUTHORITY_INVALID"

This error occurs because we're using self-signed SSL certificates for local development. Here are several solutions:

## Solution 1: Accept Certificate in Browser (Recommended for Development)

### Chrome/Edge:
1. Click "Advanced"
2. Click "Proceed to demo-register-server.local (unsafe)"

### Firefox:
1. Click "Advanced"
2. Click "Accept the Risk and Continue"

**If you got error PR_CONNECT_RESET_ERROR :**
1. Open Firefox settings (about:preferences#privacy)
2. Go to "Certificates" → "View Certificates"
3. Click on "Authorities" tab
4. Click on "Import..."
5. Select `ssl/demo-register-server.local.crt`
6. Tick "Trust this CA to identify websites"
7. Restart Firefox

### Safari:
1. Click "Show Details"
2. Click "Visit this website"

## Solution 2: Add Certificate to System Trust Store

### macOS:
```bash
# Add certificate to keychain
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ssl/demo-register-server.local.crt
```

### Linux:
```bash
# Copy certificate to system trust store
sudo cp ssl/demo-register-server.local.crt /usr/local/share/ca-certificates/
sudo update-ca-certificates
```

### Windows:
1. Double-click the `.crt` file
2. Click "Install Certificate"
3. Choose "Local Machine" → "Place all certificates in the following store"
4. Select "Trusted Root Certification Authorities"

## Solution 3: Use mkcert for Local Development

For a more professional development setup:

```bash
# Install mkcert
brew install mkcert  # macOS
# or
sudo apt install mkcert  # Ubuntu

# Install local CA
mkcert -install

# Generate certificate
mkcert demo-register-server.local localhost 127.0.0.1
```

## Testing the Configuration

Test your HTTPS endpoints:
```bash
# Test nginx (API) on port 8081
curl -k https://demo-register-server.local:8081

# Test phpMyAdmin on port 8082
curl -k https://demo-register-server.local:8082
```

Expected response:
```json
{
    "success": true,
    "client_url": "https://demo-register-server.local:8443",
    "domain": "demo-register-server.local",
    "protocol": "https",
    "timestamp": "2025-10-16 23:40:02"
}
```

## Important Security Notes

⚠️ **WARNING**: These solutions are for DEVELOPMENT ONLY!

- Self-signed certificates should never be used in production
- Always use proper SSL certificates (Let's Encrypt, commercial CA) in production
- The `-k` flag in curl bypasses certificate verification

## Production Deployment

For production, use:
- Let's Encrypt certificates
- Commercial SSL certificates
- Proper domain validation
- Regular certificate renewal
