import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/devices`

export default {
  update(deviceId, params) {
    return Client.put(`${resource}/${deviceId}`, params)
  },
  list(params = {}) {
    return Client.get(`${resource}`, { params })
  },
  deviceInfo(deviceId) {
    return Client.get(`${resource}/${deviceId}/device-info`)
  },
  capabilities(deviceId) {
    return Client.get(`${resource}/${deviceId}/capabilities`)
  },
  generateToken(deviceId, params) {
    return Client.post(`${resource}/${deviceId}/token`, params)
  },
}
