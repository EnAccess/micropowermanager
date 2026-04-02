import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  importClusters(data) {
    return Client.post(`${resource}/clusters`, { data })
  },
}
