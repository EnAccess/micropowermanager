import TransactionRepository from "../repositories/TransactionRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"

export class TransactionService {
  constructor() {
    this.repository = TransactionRepository
    this.list = []
    this.pagingUrl = "/api/spark-meters/sm-transaction"
    this.routeName = "/spark-meters/sm-transaction"
    this.paginator = new Paginator(this.pagingUrl)
    this.transaction = {
      id: null,
      transactionId: null,
      customerName: null,
      siteName: null,
      status: null,
      externalId: null,
      amount: null,
      source: null,
      memo: null,
      type: null,
      timestamp: null,
    }
  }

  fromJson(transactionData) {
    let person = transactionData.sm_customer?.mpm_person
    this.transaction = {
      id: transactionData.id,
      transactionId: transactionData.transaction_id,
      customerName: person ? `${person.name} ${person.surname}` : null,
      siteName: transactionData.site?.mpm_mini_grid?.name ?? null,
      status: transactionData.status,
      externalId: transactionData.external_id,
      amount: transactionData.amount,
      source: transactionData.source,
      memo: transactionData.memo,
      type: transactionData.type,
      timestamp: transactionData.timestamp,
    }
    return this.transaction
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
