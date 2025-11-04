# Google Pay Direct – PipraPay Gateway

![Google Pay logo](assets/icon.png)

Google Pay Direct is a payment gateway plugin for the [PipraPay](https://piprapay.com/) platform. It enables your customers to pay with their saved cards in Google Pay using **Direct tokenization (ECv2)**. With Direct tokenization you receive an encrypted payment token and process the card transaction yourself via your acquirer.

## Features

- Accept payments from Google Pay using Direct tokenization (ECv2).
- Supports **Sandbox** and **Production** modes.
- Customizable display name, min/max amounts, fixed and percent fees.
- Merchant name and optional Merchant ID (required in production, optional in test)【846602639542790†L204-L208】.
- ECv2 public key configuration – store your 65‑byte uncompressed P‑256 key【77782387575752†L600-L609】.
- Built-in **sandbox simulator**: automatically marks transactions complete for testing.
- Server‑side ECv2 decrypt stub included; replace with your own implementation when you go live.
- Responsive checkout UI with a **Pay with GPay** button.

## Installation

1. Download the plugin from [letest relese]([url](https://github.com/fattain-naime/gpay-for-piprapay/releases)) or the provided ZIP file and place it in your PipraPay Admin panel plugin uploader or installation under `pp-content/plugins/payment-gateway`.
2. Activate **Google Pay Direct** from the PipraPay admin panel.
3. Go to **Payment Gateways → Google Pay** and configure:
   - **Mode:** `Sandbox` for testing or `Live` for production.
   - **Merchant Name:** The business name shown to users.
   - **Merchant ID:** Required in production but **not needed in the test environment**【846602639542790†L204-L208】.
   - **Public Key (ECv2):** Your Base64‑encoded 65‑byte uncompressed P‑256 public key (begins with `B` or `Q`)【77782387575752†L600-L609】. See the [Google Pay crypto guide](https://developers.google.com/pay/api/web/guides/resources/payment-data-cryptography) for key generation.
   - You can use the ECv2 test key for check
   ```sh
   BBpye2KNbF/W+JK+AGubqufCUUH8w/GyCV8O1l2mqf4VRPj5xcb48eJ1cbe/UnUblFXrvvh2Q9HBgL+CDs53Pes=
   ```

## Usage

- During checkout the customer sees a **Pay with GPay** button.  
- The plugin uses the official Google Pay Web SDK to check readiness (`isReadyToPay`) and then requests payment data (`loadPaymentData`).
- In Sandbox mode, after the customer authorizes the payment, the plugin automatically simulates authorization and marks the transaction **Completed**.
- In Live mode the plugin posts the encrypted token to your server and leaves the transaction **Pending**. You must:
  1. Use your private key to decrypt the ECv2 token and verify its signature【77782387575752†L600-L609】.
  2. Submit the extracted card data and cryptogram to your acquirer for authorization.
  3. Update the transaction in PipraPay from **pending** to **completed** with the acquirer’s authorization ID.

## Generating your public/private keys

Google Pay requires your ECv2 encryption key to be in **uncompressed point format**: one magic byte (`0x04`) followed by two 32‑byte integers for the X and Y coordinates【77782387575752†L600-L609】. Run the following commands with OpenSSL (replace `key.pem` with your desired filename):

```sh
# Generate a new P-256 private key
openssl ecparam -name prime256v1 -genkey -noout -out key.pem

# Convert your public key to base64 (uncompressed point format)
openssl ec -in key.pem -pubout -text -noout 2> /dev/null \
| grep "pub:" -A5 | sed 1d | xxd -r -p | base64 | tr -d '\n\r' > publicKey.txt
```

Use the contents of `publicKey.txt` as your **Public Key (ECv2)** in the plugin settings.

## Troubleshooting

- **OR_BIBED_06: “This merchant is having trouble accepting your payment…”**  
  - Ensure you are in `Sandbox` mode and have not set a Merchant ID【846602639542790†L204-L208】.  
  - Double‑check that your public key is the Base64‑encoded uncompressed key【77782387575752†L600-L609】 – not a PEM or DER string.  
  - Verify that your checkout page is served over HTTPS.

- **The button does not appear:**  
  - Confirm that the customer has Google Pay available on their device and browser.  
  - Check the browser console for errors. The plugin logs readiness and token errors.

## Contributing

Contributions are welcome! Please open issues or pull requests on GitHub to report bugs or suggest improvements.

## License

This project is licensed under the [GPL 3.0](https://www.gnu.org/licenses/gpl-3.0.html) license.
