import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/export/debts`
export default {
  // eslint-disable-next-line no-unused-vars
  download(email, slug) {
    return `${resource}/${email}`
  },
}
