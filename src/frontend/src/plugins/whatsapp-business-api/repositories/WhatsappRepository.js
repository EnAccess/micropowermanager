import Client, { baseUrl } from "@/repositories/Client/AxiosClient.js"

const resource = `${baseUrl}/api/whatsapp-business-api`

export default {
  getCredentials() {
    return Client.get(`${resource}/credentials`)
  },
  updateCredentials(credentials) {
    return Client.put(`${resource}/credentials`, credentials)
  },
  getOptIns(params = {}) {
    return Client.get(`${resource}/opt-in`, { params })
  },
  getOptIn(personId) {
    return Client.get(`${resource}/opt-in/${personId}`)
  },
  updateOptIn(personId, data) {
    return Client.put(`${resource}/opt-in/${personId}`, data)
  },
  getOptInStats() {
    return Client.get(`${resource}/opt-in/stats`)
  },
  bulkOptIn(data) {
    return Client.post(`${resource}/opt-in/bulk`, data)
  },
}
