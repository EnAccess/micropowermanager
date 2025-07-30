import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AgentChargeRepository from "@/repositories/AgentChargeRepository"

export class AgentChargeService {
  constructor() {
    this.repository = AgentChargeRepository
    this.balance = {
      amount: null,
      agentId: null,
    }
  }

  async addNewBalance() {
    try {
      let balancePM = {
        agent_id: this.balance.agentId,
        amount: this.balance.amount,
      }
      let response = await this.repository.create(balancePM)

      if (response.status === 200 || response.status === 201) {
        this.resetNewBalance()
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  resetNewBalance() {
    this.balance = {
      amount: null,
      agentId: null,
    }
  }
}
