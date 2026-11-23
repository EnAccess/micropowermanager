import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/whatsapp`

export default {
  getCredential() {
    return Client.get(`${resource}/credential`)
  },
  updateCredential(credential) {
    return Client.put(`${resource}/credential`, credential)
  },
  testConnection() {
    return Client.post(`${resource}/credential/test`)
  },
  sendTest(payload) {
    return Client.post(`${resource}/credential/send-test`, payload)
  },
  getMessages(page = 1) {
    return Client.get(`${resource}/messages`, { params: { page } })
  },
  getOptIns(page = 1) {
    return Client.get(`${resource}/opt-ins`, { params: { page } })
  },
  addOptIn(phone) {
    return Client.post(`${resource}/opt-ins`, { phone })
  },
  revokeOptIn(id) {
    return Client.delete(`${resource}/opt-ins/${id}`)
  },
}
