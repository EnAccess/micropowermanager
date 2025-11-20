import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/spark-meters/sm-setting/feedback-word`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  put(feedBackWords) {
    return Client.put(`${resource}/${feedBackWords.id}`, feedBackWords)
  },
}
