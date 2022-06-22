import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/spark-meters/sm-setting/feedback-word`


import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    put (feedBackWords) {
        return Client.put(`${resource}/${feedBackWords.id}`, feedBackWords)
    },
}