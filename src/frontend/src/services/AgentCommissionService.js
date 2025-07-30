import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AgentCommissionRepository from "@/repositories/AgentCommissionRepository"

export class AgentCommissionService {
  constructor() {
    this.repository = AgentCommissionRepository
    this.list = []
    this.agentCommission = {
      id: null,
      name: null,
      energyCommission: null,
      applianceCommission: null,
      riskBalance: null,
    }
  }

  fromJson(data) {
    let agentCommission = {
      id: data.id,
      name: data.name,
      energyCommission: data.energy_commission,
      applianceCommission: data.appliance_commission,
      riskBalance: data.risk_balance,
    }
    return agentCommission
  }

  updateList(data) {
    this.list = data.map(this.fromJson)
    return this.list
  }

  async getAgentCommissions() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        let list = response.data.data
        this.list = this.updateList(list)
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async createAgentCommission() {
    try {
      let agentCommissionPM = {
        name: this.agentCommission.name,
        energy_commission: this.agentCommission.energyCommission,
        appliance_commission: this.agentCommission.applianceCommission,
        risk_balance: this.agentCommission.riskBalance,
      }
      let response = await this.repository.create(agentCommissionPM)
      this.resetAgentCommission()
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      this.resetAgentCommission()
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateAgentCommission(agentCommission) {
    try {
      let agentCommissionPM = {
        id: agentCommission.id,
        name: agentCommission.name,
        energy_commission: agentCommission.energyCommission,
        appliance_commission: agentCommission.applianceCommission,
        risk_balance: agentCommission.riskBalance,
      }
      let response = await this.repository.update(agentCommissionPM)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async deleteAgentCommission(agentCommissionId) {
    try {
      let response = await this.repository.delete(agentCommissionId)
      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      this.resetAgentCommission()
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  resetAgentCommission() {
    this.agentCommission = {
      id: null,
      name: null,
      energyCommission: null,
      applianceCommission: null,
      riskBalance: null,
    }
  }
}
