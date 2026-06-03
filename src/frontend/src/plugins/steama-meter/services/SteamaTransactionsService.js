import TransactionRepository from "../repositories/TransactionRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"

export class SteamaTransactionsService {
  constructor() {
    this.repository = TransactionRepository
    this.list = []
    this.pagingUrl = "/api/steama-meters/steama-transaction"
    this.routeName = "/steama-meters/steama-transactions"
    this.paginator = new Paginator(this.pagingUrl)
    this.steamaTransaction = {
      id: null,
      transactionId: null,
      customerName: null,
      siteName: null,
      amount: null,
      category: null,
      provider: null,
      timestamp: null,
    }
  }

  fromJson(transactionData) {
    let person = transactionData.stm_customer?.mpm_person
    this.steamaTransaction = {
      id: transactionData.id,
      transactionId: transactionData.transaction_id,
      customerName: person ? `${person.name} ${person.surname}` : null,
      siteName: transactionData.site?.mpm_mini_grid?.name ?? null,
      amount: transactionData.amount,
      category: transactionData.category,
      provider: transactionData.provider,
      timestamp: transactionData.timestamp,
    }
    return this.steamaTransaction
  }

  updateList(data) {
    this.list = []
    for (let t in data) {
      let transaction = this.fromJson(data[t])
      this.list.push(transaction)
    }
  }

  async syncTransactions() {
    try {
      let response = await this.repository.sync()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message ?? e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
