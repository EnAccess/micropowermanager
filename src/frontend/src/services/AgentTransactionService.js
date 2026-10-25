import { Paginator } from "@/Helpers/Paginator.js"
import AgentTransactionRepository from "@/repositories/AgentTransactionRepository.js"

export class AgentTransactionService {
  constructor(agentId) {
    this.repository = AgentTransactionRepository
    this.list = []
    this.paginator = new Paginator(resources.agents.transactions + agentId)
  }

  fromJson(data) {
    const person = data.device?.person
    return {
      id: data.id,
      amount: data.amount,
      meter: data.message,
      customer: person ? `${person.name} ${person.surname}` : "-",
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
