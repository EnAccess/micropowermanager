import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/operator/dashboard`

export default {
  platform() {
    return Client.get(`${resource}`)
  },
  tenant(companyId) {
    return Client.get(`${resource}/tenants/${companyId}`)
  },
  refresh() {
    return Client.post(`${resource}/refresh`)
  },
  refreshTenant(companyId) {
    return Client.post(`${resource}/tenants/${companyId}/refresh`)
  },
}
