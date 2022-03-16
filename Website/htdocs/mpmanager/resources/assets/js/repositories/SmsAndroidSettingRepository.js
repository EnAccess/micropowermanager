const resource = '/api/sms-android-setting'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    update (smsAndroidSetting) {
        return Client.put(`${resource}/${smsAndroidSetting.id}`, smsAndroidSetting)
    },
    create (smsAndroidSetting) {
        return Client.post(`${resource}`, smsAndroidSetting)
    },
    delete (smsAndroidSettingId) {
        return Client.delete(`${resource}/${smsAndroidSettingId}`)
    }
}