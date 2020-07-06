# fix-copyright

This tool is a one-shot project created to convert the copyright statements in the Joomla project
to a standardised format. It might be useful for other projects as well.

## Why bother

Until 2020, Joomla used to use this format:

> Copyright (C) 2005 - 2020 Open Source Matters. All rights reserved.

The ending year of the range had to be updated each year for each file. Although this update
was done by a script, this process kept polluting the file history all the time without any benefit.

Thus, the Production Department leadership of Joomla decided in mid-2020 to follow the 
advice in this excellent article about [how and why to properly write copyright statements](https://matija.suklje.name/how-and-why-to-properly-write-copyright-statements-in-your-code).

Some people consider this change pointless, but, as [Michael Babker nailed it](https://github.com/joomla/joomla-cms/pull/29689#issuecomment-646229342),

> This might come across as a pointless change, but the other pointless change is 
> modifying every file in every Joomla owned repository in January to amend the ending 
> date of the copyright claim. Additionally, a copyright claim is being made on every
> file in most every Joomla owned repository of a copyright dating back to 2005, which
> is clearly not factual.

## How it works

The original approach to determine the creation date for a file was

```bash
YEAR=$(git log --follow --date=format:%Y --pretty=format:"%cd" --diff-filter=A --find-renames=40% "${FILE}" | tail -n 1)
```

However, the results were disappointing. `git` itself uses content similarity to find renames, which led to
unexpected results.

The `Git > Show History` function of PhpStorm, on the other hand, gave very plausible results for the first commit,
and some research revealed the [implementation in IntelliJ](https://github.com/JetBrains/intellij-community/blob/ea20241265f9fb956c5a99b1023765aa0e941979/plugins/git4idea/src/git4idea/history/GitFileHistory.java) (which is the base for PhpStorm).
The people at JetBrains found that 

> `git log --follow` does detect renames, but it has a bug - merge commits
aren't handled properly: they just disappear from the history. See http://kerneltrap.org/mailarchive/git/2009/1/30/4861054
and the whole thread about that: --follow is buggy, but maybe it won't be fixed.

The solution, which is re-implemented here, is to

1. Get the first commit of the file *with that name*
2. Get the status (`Added`, `Copied` or `Renamed`) of that commit
3. Stop, if status is `Added` or `Copied`, this really is the first commit.
4. Status is `Renamed`, so get the first commit of the file *with the previous name* before the current commit.
5. Continue with step 2.
 
## How to adopt the scripts for your environment

In `fix-copyright.sh`, change lines 4-7 to suit your settings:

```bash
GREP_PATTERN="(Copyright )?\(C\) .* Open Source Matters.*All rights reserved\.?"
SED_PATTERN="\(Copyright \)\?(C) .* Open Source Matters.*All rights reserved\.\?"
OWNER="Open Source Matters, Inc."
CONTACT="https://www.joomla.org"
```

Be aware of the different kinds of escaping for `grep` rsp. `sed`.

You might want to adjust the default year in lines 18 and 20 (here the default year is `2005`):
```bash
    if [[ ${FILE} == *.xml ]]; then
      REPLACEMENT="(C) ${YEAR:-2005} ${OWNER}"
    else
      REPLACEMENT="(C) ${YEAR:-2005} ${OWNER} <${CONTACT}>"
    fi
```

## ToDo

- [ ] Move functionality from `fix-copyright.sh` to `fix-copyright.php`
- [ ] Provide `PATTERN`, `OWNER` and `CONTACT` as command line parameters
- [ ] Escape pattern internally 