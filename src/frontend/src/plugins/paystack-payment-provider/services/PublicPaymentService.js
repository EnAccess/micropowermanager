import Client, { baseUrl } from "@/repositories/Client/AxiosClient"

export class PublicPaymentService {
  constructor() {
    this.paymentRequest = {
      meterSerial: null,
      amount: null,
      currency: "NGN",
    }
  }

  async getCompanyInfo(companyHash, companyId) {
    const response = await Client.get(
      `${baseUrl}/api/paystack/public/payment/${companyHash}/${companyId}`,
    )
    return response.data
  }

  async validateMeter(companyHash, companyId, meterSerial) {
    const response = await Client.post(
      `${baseUrl}/api/paystack/public/validate-meter/${companyHash}/${companyId}`,
      {
        meter_serial: meterSerial,
      },
    )
    return response.data
  }

  async initiatePayment(companyHash, companyId, paymentData) {
    const response = await Client.post(
      `${baseUrl}/api/paystack/public/payment/${companyHash}/${companyId}`,
      {
        meter_serial: paymentData.meterSerial,
        amount: parseFloat(paymentData.amount),
        currency: paymentData.currency,
      },
    )
    return response.data
  }

  async getPaymentResult(companyHash, companyId, reference) {
    const response = await Client.get(
      `${baseUrl}/api/paystack/public/result/${companyHash}/${companyId}`,
      {
        params: {
          reference: reference,
        },
      },
    )
    return response.data
  }

  async verifyTransaction(companyHash, companyId, reference) {
    const response = await Client.get(
      `${baseUrl}/api/paystack/public/verify/${companyHash}/${companyId}`,
      {
        params: {
          reference: reference,
        },
      },
    )
    return response.data
  }
}
