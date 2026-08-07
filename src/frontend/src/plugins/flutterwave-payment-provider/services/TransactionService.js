import { Paginator } from "@/Helpers/Paginator.js"
import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/flutterwave`

export class TransactionService {
  constructor() {
    this.list = []
    this.paginator = new Paginator(`/api/flutterwave/transactions`)
  }

  updateList(data) {
    this.list = []
    if (Array.isArray(data)) {
      this.list = data
    }
  }

  async getTransactions() {
    try {
      const response = await Client.get(`${resource}/transactions`)
      return response
    } catch (error) {
      console.error("Error fetching transactions:", error)
      throw error
    }
  }

  async getTransaction(id) {
    try {
      const response = await Client.get(`${resource}/transactions/${id}`)
      return response
    } catch (error) {
      console.error("Error fetching transaction:", error)
      throw error
    }
  }

  async createTransaction(transactionData) {
    try {
      const response = await Client.post(
        `${resource}/transaction/initialize`,
        transactionData,
      )
      return response
    } catch (error) {
      console.error("Error creating transaction:", error)
      throw error
    }
  }

  // Flutterwave verifies by its own numeric transaction id, not by our
  // reference — that id only becomes available once a webhook or the public
  // result page has recorded it.
  async verifyTransaction(transactionId) {
    try {
      const response = await Client.get(
        `${resource}/transaction/verify/${transactionId}`,
      )
      return response
    } catch (error) {
      console.error("Error verifying transaction:", error)
      throw error
    }
  }

  async updateTransaction(id, transactionData) {
    try {
      const response = await Client.put(
        `${resource}/transactions/${id}`,
        transactionData,
      )
      return response
    } catch (error) {
      console.error("Error updating transaction:", error)
      throw error
    }
  }

  async deleteTransaction(id) {
    try {
      const response = await Client.delete(`${resource}/transactions/${id}`)
      return response
    } catch (error) {
      console.error("Error deleting transaction:", error)
      throw error
    }
  }
}
