import AgentDashboardRepository from "@/repositories/AgentDashboardRepository"

export class AgentDashboardService {
  constructor() {
    this.repository = AgentDashboardRepository
  }

  async getAgentPerformanceMetrics(
    period = "monthly",
    startDate = null,
    endDate = null,
  ) {
    try {
      const response = await this.repository.getAgentPerformanceMetrics(
        period,
        startDate,
        endDate,
      )
      if (response.status === 200) {
        return response.data
      } else {
        throw new Error("Failed to fetch agent performance metrics")
      }
    } catch (error) {
      console.error("Error fetching agent performance metrics:", error)
      throw error
    }
  }

  async getAgentList() {
    try {
      const response = await this.repository.getAgentList()
      if (response.status === 200) {
        return response.data.data || response.data
      } else {
        throw new Error("Failed to fetch agent list")
      }
    } catch (error) {
      console.error("Error fetching agent list:", error)
      throw error
    }
  }
}
