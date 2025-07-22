import { Paginator } from "@/Helpers/Paginator"
import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AgentReceiptRepository from "@/repositories/AgentReceiptRepository"
export class AgentReceiptService {
  constructor(agentId) {
    this.repository = AgentReceiptRepository
    this.list = []
    this.receipt = {
      id: null,
      amount: null,
      receiver: null,
      createdAt: null,
    }
    this.newReceipt = {
      agentId: null,
      amount: null,
    }
    this.paginator = new Paginator(resources.agents.receipts + "/" + agentId)
  }

  fromJson(data) {
    let receipt = {
      id: data.id,
      amount: data.amount,
      receiver: data.user.name,
      createdAt: data.created_at
        .toString()
        .replace(/T/, " ")
        .replace(/\..+/, ""),
    }
    return receipt
  }

  updateList(data) {
    this.list = data.map(this.fromJson)
    return this.list
  }

  async addNewReceipt() {
    try {
      const receiptPM = {
        agent_id: this.newReceipt.agentId,
        amount: this.newReceipt.amount,
      }
      let response = await this.repository.create(receiptPM)
      if (response.status === 200 || response.status === 201) {
        this.resetNewReceipt()
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  resetNewReceipt() {
    this.newReceipt = {
      agentId: null,
      amount: null,
    }
  }
}
