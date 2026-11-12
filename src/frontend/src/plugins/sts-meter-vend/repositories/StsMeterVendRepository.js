import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/sts-meter-vend`

export default {
  status() {
    return Client.get(`${resource}/status`)
  },
  importKeys(formData) {
    return Client.post(`${resource}/keys/import`, formData)
  },
  keyTemplate() {
    return Client.get(`${resource}/keys/template`, { responseType: "blob" })
  },
}
