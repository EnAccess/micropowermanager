import Client from "@/repositories/Client/AxiosClient.js"

export class PublicPaymentService {
  constructor() {
    this.paymentRequest = {
      deviceSerial: null,
      deviceType: "meter",
      amount: null,
    }
  }

  async getCompanyInfo(companyHash, companyIdToken) {
    const response = await Client.get(
      `/api/pesapal/public/payment/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
    )
    return response.data
  }

  async validateDevice(companyHash, companyIdToken, deviceSerial, deviceType) {
    const response = await Client.post(
      `/api/pesapal/public/validate-meter/${companyHash}?ct=${encodeURIComponent(
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
      `/api/pesapal/public/payment/${companyHash}?ct=${encodeURIComponent(
        companyIdToken,
      )}`,
      {
        device_serial: paymentData.deviceSerial,
        device_type: paymentData.deviceType,
        amount: parseFloat(paymentData.amount),
      },
    )
    return response.data
  }

  async getPaymentResult(companyHash, companyIdToken, reference) {
    const response = await Client.get(
      `/api/pesapal/public/result/${companyHash}?ct=${encodeURIComponent(
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
      `/api/pesapal/public/verify/${companyHash}?ct=${encodeURIComponent(
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
