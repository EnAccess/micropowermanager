import { ErrorHandler } from "@/Helpers/ErrorHandler"
import AgentRepository from "../repositories/AgentRepository"

export class AgentService {
  constructor() {
    this.repository = AgentRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/steama-meters/steama-agent"
    this.routeName = "/steama-meters/steama-agent"
    this.agent = {
      id: null,
      name: null,
      surname: null,
      phone: null,
      siteName: null,
      isCreditLimited: null,
      creditBalance: null,
    }
  }

  fromJson(agentData) {
    this.agent = {
      id: agentData.id,
      name: agentData.mpm_agent.person.name,
      surname: agentData.mpm_agent.person.surname,
      phone: agentData.mpm_agent.person.addresses[0].phone,
      siteName: agentData.site.mpm_mini_grid.name,
      isCreditLimited: agentData.is_credit_limited,
      creditBalance: agentData.credit_balance,
    }
    return this.agent
  }

  updateList(data) {
    this.list = []
    for (let a in data) {
      let agent = this.fromJson(data[a])
      this.list.push(agent)
    }
  }
  async syncAgents() {
    try {
      let response = await this.repository.sync()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async checkAgents() {
    try {
      let response = await this.repository.syncCheck()
      if (response.status === 200) {
        return response.data.data.result
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async getAgentsCount() {
    try {
      let response = await this.repository.count()
      if (response.status === 200) {
        this.count = response.data
        return this.count
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
