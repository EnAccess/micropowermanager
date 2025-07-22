import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { Paginator } from "@/Helpers/Paginator"
import UserTransactionsRepository from "@/repositories/UserTransactionsRepository"

export class UserTransactionsService {
  constructor(personId) {
    this.repository = UserTransactionsRepository
    this.list = []
    this.personId = personId
    this.paginator = new Paginator("/api/people/" + personId + "/transactions")
  }

  updateList(transactionList) {
    this.list = transactionList.map((transaction) => {
      return this.fromJson(transaction)
    })
    return this.list
  }

  fromJson(transactionData) {
    return {
      id: transactionData.transaction_id,
      paymentType: transactionData.payment_type,
      sender: transactionData.sender,
      amount: transactionData.amount,
      type: transactionData.paid_for_type,
      paymentService: transactionData.payment_service,
      createdAt: transactionData.created_at,
    }
  }

  async getTransactions(userId, page) {
    try {
      let response = await this.repository.list(userId, page)
      if (response.status === 200) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
