import { Paginator } from "@/Helpers/Paginator"

import AgentTransactionRepository from "@/repositories/AgentTransactionRepository"

export class AgentTransactionService {
  constructor(agentId) {
    this.repository = AgentTransactionRepository
    this.list = []
    this.agentId = null
    this.transaction = {
      id: null,
      amount: null,
      meter: null,
      customer: null,
      createdAt: null,
    }
    this.paginator = new Paginator(resources.agents.transactions + agentId)
  }

  fromJson(data) {
    return {
      id: data.id,
      amount: data.amount,
      meter: data.message,
      customer: data.device.person.name + " " + data.device.person.surname,
      createdAt: data.created_at
        .toString()
        .replace(/T/, " ")
        .replace(/\..+/, ""),
    }
  }

  updateList(data) {
    this.list = []
    this.list = data.map((transaction) => {
      return this.fromJson(transaction)
    })
    return this.list
  }
}
