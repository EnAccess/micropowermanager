import Client, { baseUrl } from "@/repositories/Client/AxiosClient"

export class PublicPaymentService {
  constructor() {
    this.paymentRequest = {
      deviceSerial: null,
      deviceType: "meter",
      amount: null,
      currency: "NGN",
    }
  }

  async getCompanyInfo(companyHash, companyIdToken) {
    const response = await Client.get(
      `${baseUrl}/api/paystack/public/payment/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
    )
    return response.data
  }

  async validateDevice(companyHash, companyIdToken, deviceSerial, deviceType) {
    const response = await Client.post(
      `${baseUrl}/api/paystack/public/validate-meter/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
      {
        device_serial: deviceSerial,
        device_type: deviceType,
      },
    )
    return response.data
  }

  async initiatePayment(companyHash, companyIdToken, paymentData) {
    const response = await Client.post(
      `${baseUrl}/api/paystack/public/payment/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
      {
        device_serial: paymentData.deviceSerial,
        device_type: paymentData.deviceType,
        amount: parseFloat(paymentData.amount),
        currency: paymentData.currency,
      },
    )
    return response.data
  }

  async getPaymentResult(companyHash, companyIdToken, reference) {
    const response = await Client.get(
      `${baseUrl}/api/paystack/public/result/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
      {
        params: {
          reference: reference,
        },
      },
    )
    return response.data
  }

  async verifyTransaction(companyHash, companyIdToken, reference) {
    const response = await Client.get(
      `${baseUrl}/api/paystack/public/verify/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
      {
        params: {
          reference: reference,
        },
      },
    )
    return response.data
  }
}
