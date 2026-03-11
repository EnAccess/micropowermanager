import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  getStatus(jobId) {
    return Client.get(`${resource}/status/${jobId}`)
  },
}
