#### 2.1.1 (2021-09-27)

##### New Features

*  Added Yoast detection. ([437838b1](https://github.com/GraysonE/data-sync/commit/437838b1cf01177c8e8a9163ca75013125f484fd))

### 2.1.0 (2021-09-27)

##### Bug Fixes

*  PHP 8.0 compatibility. ([dd1f6211](https://github.com/GraysonE/data-sync/commit/dd1f6211e0f7f320865aadbbee5482cab855f92a))
*  Fixed post detail errors. ([cf7f1f32](https://github.com/GraysonE/data-sync/commit/cf7f1f32ade51eed6f3569b398fddeef0df81005))
*  Fixed concerning options error message and addtional load errors. ([0ef053df](https://github.com/GraysonE/data-sync/commit/0ef053df9abcc9727bf8ebcd008d1fc4b9a1fbb0))
*  Fixed several initial install bugs. ([969d41b3](https://github.com/GraysonE/data-sync/commit/969d41b377996b97352577ad6c096765677846f6))

##### Performance Improvements

*  Updated dependencies ([da1f3985](https://github.com/GraysonE/data-sync/commit/da1f39857002427dff34297e8bbef7e6d11dcdc8))

#### 2.0.2 (2020-06-18)

##### Bug Fixes

*  Updated all routes to be compatible with v5.4.2 of WordPress. ([da124c24](https://github.com/GraysonE/data-sync/commit/da124c2457bbfc46064c5f6b9b28105a984d6a9e))

#### 2.0.1 (2020-04-06)

##### Bug Fixes

*  Templates path had two slashes. ([91b5d104](https://github.com/GraysonE/data-sync/commit/91b5d1042318669d7d7d77d0bf44a03b7bcf0146))
*  Removed hard-coded path in Logs. ([d59a1035](https://github.com/GraysonE/data-sync/commit/d59a10358001d4c8cc2855e8d10bbbbf61e1fd09))
*  Images in ACF wysiwyg areas now get synced. ([f064a217](https://github.com/GraysonE/data-sync/commit/f064a2176f02609d40c6db18ef84af24751a507b))
*  Silenced empty error messages. ([e4d9e8bd](https://github.com/GraysonE/data-sync/commit/e4d9e8bdcf4840cfe78d4e77732aacb3f58d9ee9))
*  Validation bug that was trying to make an object an array. Removed debugging. ([5cc59a00](https://github.com/GraysonE/data-sync/commit/5cc59a00ec32f4a45e743c6993d465f1753241b5))
*  Validation bug that was trying to make an object an array. ([d0188b68](https://github.com/GraysonE/data-sync/commit/d0188b68e57e41549fd7713305b866aa39cd0566))

## 2.0.0 (2020-03-30)

##### Build System / Dependencies

*  Built production assets. ([3273798f](https://github.com/GraysonE/data-sync/commit/3273798f5d510098abe408b95189509801a24980))

##### Documentation Changes

*  Updated readme. ([2448d5ed](https://github.com/GraysonE/data-sync/commit/2448d5ede0b2154ed759ae38c8fe4bc8f4864387))

##### Bug Fixes

*  CORS bugs resolved on local and staging environments. ([3bb8f0a4](https://github.com/GraysonE/data-sync/commit/3bb8f0a426dae2a32f6e47ff11c8ad9eb757552a))
*  Custom permalinks are now allowed. ([0629dcb1](https://github.com/GraysonE/data-sync/commit/0629dcb18e30a2a66018fe7d2991b36532ce3383))

## 2.0.0 (2020-03-30)

##### Build System / Dependencies

*  Built production assets. ([3273798f](https://github.com/GraysonE/data-sync/commit/3273798f5d510098abe408b95189509801a24980))

##### Documentation Changes

*  Updated readme. ([2448d5ed](https://github.com/GraysonE/data-sync/commit/2448d5ede0b2154ed759ae38c8fe4bc8f4864387))

##### Bug Fixes

*  CORS bugs resolved on local and staging environments. ([3bb8f0a4](https://github.com/GraysonE/data-sync/commit/3bb8f0a426dae2a32f6e47ff11c8ad9eb757552a))
*  Custom permalinks are now allowed. ([0629dcb1](https://github.com/GraysonE/data-sync/commit/0629dcb18e30a2a66018fe7d2991b36532ce3383))

#### 1.0.1 (2020-02-06)

##### Build System / Dependencies

*  v1.0.1-beta release. Contains new auto-versioning for changelog. Fixed post type processing on receiver side deleting all saved enabled post types when the setting 'Automatically Enable New Custom Post Types On Receiver' was turned off. Cleaned up PostTypes.php with phpcs. ([092cfb12](https://github.com/GraysonE/data-sync/commit/092cfb1287e4094c6d7d1672267a69b0de82e2ef))

##### Documentation Changes

*  updated changelog with generate-changelog npm package. ([9cab07e1](https://github.com/GraysonE/data-sync/commit/9cab07e18a8be1d8c81d3f40c7f3841aced6f005))

##### New Features

*  Added release version and changelog scripts. ([ae327287](https://github.com/GraysonE/data-sync/commit/ae327287592fcc405698c2f19ad6f198a70fffb5))

##### Bug Fixes

*  Featured images were getting updated even though the post was diverged. A check for diverged synced posts has now been added to the media method that runs before the media is synced. ([56bec5c1](https://github.com/GraysonE/data-sync/commit/56bec5c1be4f62cfb74e8086ca3b79f0802e3d50))

##### Other Changes

* **build:**  v1.0.1-beta release. Contains new auto-versioning for changelog. Fixed post type processing on receiver side deleting all saved enabled post types when the setting 'Automatically Enable New Custom Post Types On Receiver' was turned off ([9d3fd5ad](https://github.com/GraysonE/data-sync/commit/9d3fd5addb2ef59bc3a93e1acf20e089a0a35c07))

#### 1.0.0 (2020-01-27)

##### Build System / Dependencies

*  v1.0.1-beta release. Contains new auto-versioning for changelog. Fixed post type processing on receiver side deleting all saved enabled post types when the setting 'Automatically Enable New Custom Post Types On Receiver' was turned off. Cleaned up PostTypes.php with phpcs. ([092cfb12](https://github.com/GraysonE/data-sync/commit/092cfb1287e4094c6d7d1672267a69b0de82e2ef))

##### Other Changes

* **build:**  v1.0.1-beta release. Contains new auto-versioning for changelog. Fixed post type processing on receiver side deleting all saved enabled post types when the setting 'Automatically Enable New Custom Post Types On Receiver' was turned off ([9d3fd5ad](https://github.com/GraysonE/data-sync/commit/9d3fd5addb2ef59bc3a93e1acf20e089a0a35c07))
* //github.com/GraysonE/data-sync ([387006ce](https://github.com/GraysonE/data-sync/commit/387006ce225cbeb91dd77e1abe8ca47f702f4230))
* //github.com/GraysonE/data-sync ([0250c3f4](https://github.com/GraysonE/data-sync/commit/0250c3f4b29e73b4bf0125a6455f9df57be6f41a))
* //github.com/GraysonE/data-sync ([4cae8d5d](https://github.com/GraysonE/data-sync/commit/4cae8d5de8f90d1ea3292c2c640ac102b5e20bf6))
* //github.com/GraysonE/data-sync ([dbb76ba4](https://github.com/GraysonE/data-sync/commit/dbb76ba4101c5d30b2a35ff0bf2222ee568e85ba))
* //input ([b2b341db](https://github.com/GraysonE/data-sync/commit/b2b341db8c45395396dfaffac94cc1d7ef85694d))

