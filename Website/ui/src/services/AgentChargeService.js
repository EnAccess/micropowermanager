import { ErrorHandler } from "@/Helpers/ErrorHander"

import AgentChargeRepository from "@/repositories/AgentChargeRepository"

export class AgentChargeService {
  constructor() {
    this.repository = AgentChargeRepository
    this.newBalance = {
      userId: null,
      amount: null,
      agentId: null,
    }
  }

  async addNewBalance() {
    try {
      let newBalancePM = {
        user_id: this.newBalance.userId,
        amount: this.newBalance.amount,
      }
      let response = await this.repository.create(
        newBalancePM,
        this.newBalance.agentId,
      )
      if (response.status === 200 || response.status === 201) {
        this.resetNewBalance()
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  resetNewBalance() {
    this.newBalance = {
      personId: null,
      amount: null,
      agentId: null,
    }
  }
}
