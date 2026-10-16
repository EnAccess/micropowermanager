import Client from "@/repositories/Client/AxiosClient.js"

const credentialResource = `/api/whatsapp/credential`
const statusResource = `/api/whatsapp/status`
const templateResource = `/api/whatsapp/templates`
const messageResource = `/api/whatsapp/messages`
const testMessageResource = `/api/whatsapp/test-message`

export default {
  getCredential() {
    return Client.get(credentialResource)
  },
  updateCredential(payload) {
    return Client.put(credentialResource, payload)
  },
  getStatus() {
    return Client.get(statusResource)
  },
  getTemplates() {
    return Client.get(templateResource)
  },
  createTemplate(payload) {
    return Client.post(templateResource, payload)
  },
  updateTemplate(id, payload) {
    return Client.put(`${templateResource}/${id}`, payload)
  },
  getMessages(status) {
    return Client.get(messageResource, {
      params: status ? { status } : {},
    })
  },
  sendTestMessage(payload) {
    return Client.post(testMessageResource, payload)
  },
}
