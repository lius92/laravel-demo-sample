<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Mail;
use Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth',[
            //除了下面三个方法外都要进行验证
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
        ]);

        $this->middleware('guest', [
            'only' => ['create']
        ]);
    }

    public function index()
    {
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        //用户注册页面 直接渲染注册表单视图
        return view('users.create');
    }

    /**
     * @param User $user
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function store(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6|max:16'
        ]);

        //存储数据
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        //Auth::login($user);

        $this->sendEmailConfirmationTo($user);

        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');

        return redirect('/');
    }

    public function edit(User $user)
    {
        /**
         * 1. 新建授权策略类 app/Prolicies/UserPolicy.php
         * 2. 添加授权策略方法 如：update
         * 3. 在 app/Providers/AuthServiceProvider 类中对授权策略进行设置
         *    为$policies属性添加一条模型对应到管理它们的授权策略上 如：
         *     protected $policies = [
         *           'App\Model' => 'App\Policies\ModelPolicy',
         *           \App\Models\User::class => \App\Policies\UserPolicy::class
         *     ];
         * 4. 在需要进行策略授权的控制器方法中使用 authorize 方法来验证用户授权策略
         *    $this->authorize('update', $user);
         *    第一个参数授权策略方法名称,第二个为进行授权验证的数据
         */

        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);

        $this->validate($request, [
            'name' => 'required|min:5|max:10',
            'password' => 'nullable|min:6|max:16|confirmed'
        ]);

        $data = [];
        if ($request->name) {
            $data['name'] = $request->name;
        }
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        session()->flash('success', '保存成功');

        return redirect()->route('users.show', [$user]);
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        //$user->state = 1;
        //$user->save();

        session()->flash('success', '删除成功');
        return back();
    }

    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你,激活成功！');
        return redirect()->route('users.show', [$user]);
    }

    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $from = 'aufree@yousails.com';
        $name = 'Aufree';
        $to = $user->email;
        $subject = "感谢注册 Sample 应用！请确认你的邮箱。";

        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }

}
