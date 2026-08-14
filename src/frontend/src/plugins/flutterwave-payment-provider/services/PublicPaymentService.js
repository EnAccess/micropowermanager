import Client from "@/repositories/Client/AxiosClient.js"

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
      `/api/flutterwave/public/payment/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
    )
    return response.data
  }

  async validateDevice(companyHash, companyIdToken, deviceSerial, deviceType) {
    const response = await Client.post(
      `/api/flutterwave/public/validate-meter/${companyHash}?ct=${encodeURIComponent(
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
      `/api/flutterwave/public/payment/${companyHash}?ct=${encodeURIComponent(
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

  async getPaymentResult(
    companyHash,
    companyIdToken,
    reference,
    transactionId,
  ) {
    const response = await Client.get(
      `/api/flutterwave/public/result/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
      {
        params: {
          reference: reference,
          transaction_id: transactionId,
        },
      },
    )
    return response.data
  }

  async verifyTransaction(companyHash, companyIdToken, transactionId) {
    const response = await Client.get(
      `/api/flutterwave/public/verify/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
      {
        params: {
          transaction_id: transactionId,
        },
      },
    )
    return response.data
  }
}
