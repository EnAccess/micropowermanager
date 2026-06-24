import { Paginator } from "@/Helpers/Paginator.js"
import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/safaricom`

export class TransactionService {
  constructor() {
    this.list = []
    this.paginator = new Paginator(`/api/safaricom/transactions`)
  }

  updateList(data) {
    this.list = []
    if (Array.isArray(data)) {
      this.list = data
    }
  }

  async getTransactions() {
    try {
      return await Client.get(`${resource}/transactions`)
    } catch (error) {
      console.error("Error fetching Safaricom transactions:", error)
      throw error
    }
  }

  async getTransaction(id) {
    try {
      return await Client.get(`${resource}/transactions/${id}`)
    } catch (error) {
      console.error("Error fetching Safaricom transaction:", error)
      throw error
    }
  }

  async initiateStkPush(payload) {
    try {
      return await Client.post(`${resource}/stk-push`, payload)
    } catch (error) {
      console.error("Error initiating STK Push:", error)
      throw error
    }
  }

  async getStatus(referenceId) {
    try {
      return await Client.get(`${resource}/transaction/${referenceId}/status`)
    } catch (error) {
      console.error("Error fetching STK Push status:", error)
      throw error
    }
  }

  async validateDevice(deviceSerial, deviceType) {
    try {
      const response = await Client.post(`${resource}/validate-device`, {
        device_serial: deviceSerial,
        device_type: deviceType,
      })
      return response.data
    } catch (error) {
      console.error("Error validating device:", error)
      throw error
    }
  }
}
