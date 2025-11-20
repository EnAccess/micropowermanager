import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/dashboard/agents`

export default {
  getAgentPerformanceMetrics(
    period = "monthly",
    startDate = null,
    endDate = null,
  ) {
    const params = { period }
    if (startDate) params.start_date = startDate
    if (endDate) params.end_date = endDate

    return Client.get(resource, { params })
  },

  getAgentList() {
    return Client.get(`${baseUrl}/api/agents`)
  },
}
