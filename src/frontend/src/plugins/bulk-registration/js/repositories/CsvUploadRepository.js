import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/bulk-register/import-csv`

export default {
  post(csvData) {
    return Client.post(`${resource}`, csvData)
  },
}
