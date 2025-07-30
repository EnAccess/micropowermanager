import { Paginator } from "@/Helpers/Paginator"
import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { resources } from "@/resources"
import { EventBus } from "@/shared/eventbus"
import TransactionRepository from "@/repositories/TransactionRepository"

export class Transactions {
  constructor(meterId) {
    this.tokens = []
    this.paginator = new Paginator(
      resources.meters.transactions + meterId + "/transactions",
    )
  }

  updateList(data) {
    this.tokens = []
    for (let t in data) {
      this.tokens.push(data[t])
    }
  }
}

export class TransactionService {
  constructor() {
    this.repository = TransactionRepository
    this.list = []
    this.transaction = {
      id: null,
      service: null,
      sender: null,
      amount: null,
      type: null,
      message: null,
      sentDate: null,
      lastUpdate: null,
      status: null,
    }
    this.paginator = new Paginator(resources.transactions.list.all)
    this.analyticsData = null
    this.transactionJson = null
  }

  async getTransactions() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.list = this.updateList(response.data.data)
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getAnalytics(period) {
    try {
      this.analyticsData = null
      let response = await this.repository.analytics(period)
      if (response.status === 200) {
        this.analyticsData = response.data
        return this.analyticsData
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getFilteredTransactions(term) {
    try {
      let response = await this.repository.filteredList(term)
      if (response.status === 200) {
        this.list = this.updateList(response.data.data)
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getTransaction(id) {
    try {
      let response = await this.repository.get(id)

      if (response.status === 200 || response.status === 201) {
        this.transactionJson = response.data.data
        if (this.transactionJson.payment_histories.length === 0) {
          this.transactionJson.payment_histories.push({
            personName: "---",
            paymentHistory: false,
          })
        } else {
          this.transactionJson.payment_histories[0].paymentHistory = true
        }
        return this.transactionJson
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  searchAdvanced(data) {
    this.paginator = new Paginator(resources.transactions.searchAdvanced)
    EventBus.$emit("loadPage", this.paginator, data)
  }

  updateList(transactionList) {
    this.list = transactionList.map((transaction) => {
      return this.fromJson(transaction)
    })
    return this.list
  }

  fromJson(transactionData) {
    return {
      id: transactionData.id,
      service: transactionData.original_transaction_type,
      sender: transactionData.sender,
      amount: transactionData.amount,
      type: transactionData.type,
      message: transactionData.message,
      sentDate: transactionData.created_at,
      lastUpdate: transactionData.updated_at,
      status: this.getOriginalData(transactionData).status,
    }
  }

  getOriginalData(transactionData) {
    if (transactionData.original_transaction !== undefined) {
      return transactionData.original_transaction
    } else if (
      transactionData.original_transaction_type === "airtel_transaction"
    ) {
      return transactionData.original_airtel
    } else if (
      transactionData.original_transaction_type === "vodacom_transaction"
    ) {
      return transactionData.original_vodacom
    } else if (
      transactionData.original_transaction_type === "third_party_transaction"
    ) {
      return transactionData.original_third_party
    }
  }
}
