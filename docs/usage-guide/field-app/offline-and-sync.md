---
order: 5
---

# Working offline and syncing

The app needs an internet connection for most tasks.
Only customer registration works without one.

| Task                                           | Works offline? |
| ---------------------------------------------- | -------------- |
| Registering a customer                         | Yes            |
| Assigning a meter, adding documents or answers | No             |
| Selling a solar home system                    | No             |
| Collecting a payment and generating a token    | No             |

A customer registered offline is saved on the phone and marked **PENDING SYNC**.
The app sends it to the server automatically when you are back online, or you can tap **Sync now** on the **Customers** tab.
You can assign a meter or add documents once the customer has synced.

If the server rejects a customer (for example because the phone number is already registered), it is marked **SYNC FAILED** with the reason.
Tap it to correct the details and save again, or swipe it to the left to discard it.

> [!WARNING]
> Signing out deletes customers that have not synced yet.
> If the app warns you about unsynced customers when you sign out, tap **Sync now** first.
